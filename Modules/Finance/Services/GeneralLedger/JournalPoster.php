<?php

namespace Modules\Finance\Services\GeneralLedger;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalLine;
use Modules\Finance\Models\GeneralLedger\JournalStatus;
use RuntimeException;

/**
 * The single gate a journal passes through on its way from draft to posted.
 *
 * Every rule lives here rather than in the form so that seeders, imports, the
 * secondary-ledger replication and any future module posting through
 * Finance are held to the same standard as somebody typing into the screen.
 */
class JournalPoster
{
    /**
     * Money is compared and summed at the scale the columns are stored at.
     * Float arithmetic on six decimal places drifts, and a journal that fails
     * to balance by 0.0000001 is indistinguishable from one that is genuinely
     * wrong.
     */
    private const SCALE = 6;

    /**
     * @throws RuntimeException when any posting rule is broken
     */
    public function post(Journal $journal, ?int $userId = null): Journal
    {
        return DB::transaction(function () use ($journal, $userId): Journal {
            $journal->loadMissing(['lines.account', 'lines.costCenter', 'ledger.chart', 'fiscalPeriod', 'journalBook']);

            $this->assertPostable($journal);

            [$totalDebit, $totalCredit] = $this->totals($journal);

            $journal->forceFill([
                'number' => $journal->number ?? $this->nextNumber($journal),
                'status' => JournalStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $userId,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ])->save();

            return $journal;
        });
    }

    /**
     * Mirror a posted journal into a new one that cancels it.
     *
     * The original is marked Reversed rather than altered, so both documents
     * stay on the record and the audit trail shows what happened instead of
     * hiding it.
     *
     * @throws RuntimeException
     */
    public function reverse(Journal $journal, ?int $userId = null): Journal
    {
        return DB::transaction(function () use ($journal, $userId): Journal {
            $journal->loadMissing('lines');

            if ($journal->status !== JournalStatus::Posted) {
                throw new RuntimeException("Only a posted journal can be reversed; [{$journal->code}] is {$journal->status->value}.");
            }

            $reversal = Journal::create([
                'ledger_id' => $journal->ledger_id,
                'journal_book_id' => $journal->journal_book_id,
                'fiscal_period_id' => $journal->fiscal_period_id,
                'journal_date' => $journal->journal_date,
                'description' => __('Reversal of').' '.($journal->number ?? $journal->code),
                'source_type' => $journal->source_type,
                'source_id' => $journal->source_id,
                'source_reference' => $journal->source_reference,
                'reverses_journal_id' => $journal->getKey(),
            ]);

            foreach ($journal->lines as $line) {
                $reversal->lines()->create([
                    'line_number' => $line->line_number,
                    'account_id' => $line->account_id,
                    'description' => $line->description,
                    'currency_id' => $line->currency_id,
                    'exchange_rate' => $line->exchange_rate,
                    // Sides swap; the rate does not, so the reversal cancels the
                    // original exactly instead of leaving an FX remainder behind.
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'base_debit' => $line->base_credit,
                    'base_credit' => $line->base_debit,
                    'analysis_type' => $line->analysis_type,
                    'analysis_id' => $line->analysis_id,
                    'analysis_code' => $line->analysis_code,
                    'analysis_name' => $line->analysis_name,
                ]);
            }

            $this->post($reversal->refresh(), $userId);

            $journal->forceFill(['status' => JournalStatus::Reversed])->save();

            return $reversal;
        });
    }

    /**
     * @throws RuntimeException
     */
    protected function assertPostable(Journal $journal): void
    {
        if ($journal->status !== JournalStatus::Draft) {
            throw new RuntimeException("Journal [{$journal->code}] is already {$journal->status->value}.");
        }

        if ($journal->lines->count() < 2) {
            throw new RuntimeException("Journal [{$journal->code}] needs at least two lines.");
        }

        $period = $journal->fiscalPeriod;

        if (! $journal->ledger->acceptsPostingsIn($period)) {
            throw new RuntimeException("Period [{$period->code}] is not open in ledger [{$journal->ledger->code}].");
        }

        if (! $period->contains($journal->journal_date)) {
            throw new RuntimeException(
                "Journal date {$journal->journal_date->toDateString()} falls outside period [{$period->code}]."
            );
        }

        $chartAccountIds = $journal->ledger->chart->accounts()->pluck('accounts.id')->all();

        foreach ($journal->lines as $line) {
            $this->assertLinePostable($journal, $line, $chartAccountIds);
        }

        [$totalDebit, $totalCredit] = $this->totals($journal);

        if (bccomp($totalDebit, $totalCredit, self::SCALE) !== 0) {
            throw new RuntimeException(
                "Journal [{$journal->code}] does not balance: debits {$totalDebit}, credits {$totalCredit}."
            );
        }
    }

    /**
     * @param  array<int, int>  $chartAccountIds
     *
     * @throws RuntimeException
     */
    protected function assertLinePostable(Journal $journal, JournalLine $line, array $chartAccountIds): void
    {
        $account = $line->account;

        if (! $account->is_active) {
            throw new RuntimeException("Account [{$account->number}] is inactive.");
        }

        if (! $account->is_postable) {
            throw new RuntimeException("Account [{$account->number}] is a grouping account and accepts no postings.");
        }

        if (! in_array($account->getKey(), $chartAccountIds, true)) {
            throw new RuntimeException(
                "Account [{$account->number}] is not part of chart [{$journal->ledger->chart->code}]."
            );
        }

        if (bccomp((string) $line->base_debit, '0', self::SCALE) === 0
            && bccomp((string) $line->base_credit, '0', self::SCALE) === 0) {
            throw new RuntimeException("Line {$line->line_number} carries no amount.");
        }

        // A parent cost centre exists to total its children; posting to it
        // would double-count every report that rolls the children up into it.
        if ($line->costCenter !== null && ! $line->costCenter->is_postable) {
            throw new RuntimeException(
                "Cost centre [{$line->costCenter->number}] accepts no transactions on line {$line->line_number}."
            );
        }

        // A control account is the GL side of a subledger. Without the analysis
        // the balance can never be broken down again, which is exactly how an
        // AP aging stops reconciling to the account it is supposed to explain.
        if ($account->required_analysis_type !== null && $line->analysis_type !== $account->required_analysis_type) {
            throw new RuntimeException(
                "Account [{$account->number}] requires an analysis of type [{$account->required_analysis_type}] on line {$line->line_number}."
            );
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function totals(Journal $journal): array
    {
        $debit = '0';
        $credit = '0';

        foreach ($journal->lines as $line) {
            $debit = bcadd($debit, (string) $line->base_debit, self::SCALE);
            $credit = bcadd($credit, (string) $line->base_credit, self::SCALE);
        }

        return [$debit, $credit];
    }

    /**
     * Next document number for this journal's book and fiscal year.
     *
     * ponytail: serialized on the book's row, so two clerks numbering in the
     * same book queue behind each other. Move to a dedicated sequence table if
     * a single book ever becomes a throughput problem.
     */
    protected function nextNumber(Journal $journal): string
    {
        $book = $journal->journalBook;
        $year = $journal->fiscalPeriod->fiscalYear()->value('start_date');
        $yearLabel = $year ? substr((string) $year, 0, 4) : $journal->journal_date->format('Y');

        DB::table('journal_books')->where('id', $book->getKey())->lockForUpdate()->first();

        $prefix = $book->sequence_prefix.'-'.$yearLabel.'-';

        $lastNumber = Journal::query()
            ->where('journal_book_id', $book->getKey())
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('number')
            ->value('number');

        $next = $lastNumber ? ((int) substr((string) $lastNumber, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
