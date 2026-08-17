<?php

namespace Modules\Finance\Services\GeneralLedger;

use Illuminate\Support\Collection;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalLine;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\Finance\Models\GeneralLedger\LedgerConversionType;
use RuntimeException;

/**
 * Carries a posted primary-ledger journal into the secondary ledgers its book
 * routes it to, converting currency and filtering by chart on the way.
 *
 * Runs inside the posting transaction, not in a queue. A secondary ledger that
 * silently stops receiving entries is the worst outcome available here — the
 * books disagree and nobody finds out until year end. Failing the original
 * posting with a message naming the missing rate or account is louder, and
 * loud is what this needs to be.
 */
class JournalReplicator
{
    private const SCALE = 6;

    /**
     * @return Collection<int, Journal>
     *
     * @throws RuntimeException when a target ledger is not configured to receive this journal
     */
    public function replicate(Journal $journal): Collection
    {
        // A generated copy never replicates further: it is a leaf of the
        // original, and copying copies would multiply them without end.
        if ($journal->isGenerated()) {
            return new Collection;
        }

        $journal->loadMissing(['lines.account', 'ledger', 'journalBook']);

        $targets = $journal->journalBook->targetLedgersFor($journal->ledger);

        return $targets->map(fn (Ledger $target): Journal => $this->copyInto($journal, $target))->values();
    }

    /**
     * @throws RuntimeException
     */
    protected function copyInto(Journal $journal, Ledger $target): Journal
    {
        $this->assertTargetReady($journal, $target);

        $rate = $this->crossRate($journal, $target);
        $chartAccountIds = $target->chart->accounts()->pluck('accounts.id')->all();

        $copy = Journal::create([
            'ledger_id' => $target->getKey(),
            'journal_book_id' => $journal->journal_book_id,
            // Entity and calendar are shared with the primary, so the period is
            // the same row; only its open/closed state differs per ledger.
            'fiscal_period_id' => $journal->fiscal_period_id,
            'journal_date' => $journal->journal_date,
            'description' => $journal->description,
            'source_type' => $journal->source_type,
            'source_id' => $journal->source_id,
            'source_reference' => $journal->source_reference,
            'source_journal_id' => $journal->getKey(),
        ]);

        $lineNumber = 0;
        $debitTotal = '0';
        $creditTotal = '0';

        foreach ($journal->lines as $line) {
            $this->assertAccountCarried($line, $target, $chartAccountIds);

            $lineNumber++;
            $targetRate = bcmul((string) $line->exchange_rate, $rate, 10);
            $baseDebit = bcmul((string) $line->debit, $targetRate, self::SCALE);
            $baseCredit = bcmul((string) $line->credit, $targetRate, self::SCALE);

            $copy->lines()->create([
                'line_number' => $lineNumber,
                'account_id' => $line->account_id,
                'cost_center_id' => $line->cost_center_id,
                'description' => $line->description,
                // The transaction currency and the amount as entered are facts
                // about what happened; only the rate into base changes.
                'currency_id' => $line->currency_id,
                'exchange_rate' => $targetRate,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'base_debit' => $baseDebit,
                'base_credit' => $baseCredit,
                'analysis_type' => $line->analysis_type,
                'analysis_id' => $line->analysis_id,
                'analysis_code' => $line->analysis_code,
                'analysis_name' => $line->analysis_name,
            ]);

            $debitTotal = bcadd($debitTotal, $baseDebit, self::SCALE);
            $creditTotal = bcadd($creditTotal, $baseCredit, self::SCALE);
        }

        $this->settleRounding($copy, $target, $debitTotal, $creditTotal, $lineNumber);

        return app(JournalPoster::class)->post($copy->refresh(), $journal->posted_by);
    }

    /**
     * Converting each line on its own leaves the copy a unit or two out of
     * balance, and an unbalanced journal cannot post. The remainder goes to the
     * ledger's nominated rounding account so the copy is a faithful restatement
     * rather than an entry that quietly failed to arrive.
     */
    protected function settleRounding(
        Journal $copy,
        Ledger $target,
        string $debitTotal,
        string $creditTotal,
        int $lineNumber,
    ): void {
        $difference = bcsub($debitTotal, $creditTotal, self::SCALE);

        if (bccomp($difference, '0', self::SCALE) === 0) {
            return;
        }

        if ($target->rounding_account_id === null) {
            throw new RuntimeException(
                "Ledger [{$target->code}] needs a rounding difference account: converting this journal leaves {$difference} unmatched."
            );
        }

        $isDebitHeavy = bccomp($difference, '0', self::SCALE) === 1;
        $amount = $isDebitHeavy ? $difference : bcmul($difference, '-1', self::SCALE);

        $copy->lines()->create([
            'line_number' => $lineNumber + 1,
            'account_id' => $target->rounding_account_id,
            'description' => __('Exchange rounding difference'),
            'currency_id' => $target->base_currency_id,
            'exchange_rate' => '1',
            'debit' => $isDebitHeavy ? '0' : $amount,
            'credit' => $isDebitHeavy ? $amount : '0',
            'base_debit' => $isDebitHeavy ? '0' : $amount,
            'base_credit' => $isDebitHeavy ? $amount : '0',
        ]);
    }

    /**
     * @throws RuntimeException
     */
    protected function assertTargetReady(Journal $journal, Ledger $target): void
    {
        $target->loadMissing('chart');

        if (! $target->acceptsPostingsIn($journal->fiscalPeriod)) {
            throw new RuntimeException(
                "Period [{$journal->fiscalPeriod->code}] is closed in ledger [{$target->code}], which this journal is carried into."
            );
        }
    }

    /**
     * @param  array<int, int>  $chartAccountIds
     *
     * @throws RuntimeException
     */
    protected function assertAccountCarried(JournalLine $line, Ledger $target, array $chartAccountIds): void
    {
        if (in_array($line->account_id, $chartAccountIds, true)) {
            return;
        }

        // Dropping the line would leave the copy unbalanced by the whole amount,
        // and substituting a suspense account would hide a configuration gap
        // inside real numbers. Neither is worth the silence.
        throw new RuntimeException(
            "Account [{$line->account->number}] is missing from chart [{$target->chart->code}], so this journal cannot be carried into ledger [{$target->code}]."
        );
    }

    /**
     * Rate from the source ledger's base currency into the target's.
     *
     * @throws RuntimeException when no rate has been published
     */
    protected function crossRate(Journal $journal, Ledger $target): string
    {
        if ($target->conversion_type === LedgerConversionType::Chart) {
            return '1';
        }

        $source = $journal->ledger;

        if ($source->base_currency_id === $target->base_currency_id) {
            return '1';
        }

        $rate = ExchangeRate::resolve(
            $source->base_currency_id,
            $target->base_currency_id,
            $journal->journal_date,
            $target->rate_type,
        );

        if ($rate === null) {
            throw new RuntimeException(
                "No {$target->rate_type->value} exchange rate published on or before {$journal->journal_date->toDateString()} for ledger [{$target->code}]."
            );
        }

        return (string) $rate;
    }
}
