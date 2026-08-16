<?php

namespace Modules\Finance\Services\GeneralLedger;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Finance\Models\GeneralLedger\JournalLine;
use Modules\Finance\Models\GeneralLedger\JournalStatus;
use Modules\Finance\Models\GeneralLedger\Ledger;

/**
 * Debit and credit totals per account, in one ledger, over a date range.
 *
 * Aggregated straight from the posted lines rather than from a stored balance
 * table: there is nothing to fall out of step with, and the first thing a
 * balance table costs is an answer to "why does the report disagree with the
 * journals". A materialized balance is worth adding when a measured query gets
 * slow, not before.
 */
class TrialBalance
{
    /**
     * Reversed journals are included on purpose: reversing does not erase the
     * original, it books the opposite, and both must appear or the account
     * movement stops explaining itself.
     *
     * @return Collection<int, object{account_id: int, number: string, name: string, debit: string, credit: string}>
     */
    public function for(Ledger $ledger, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $visibleAccountIds = app(AccountAccessResolver::class)->visibleAccountIds();

        return JournalLine::query()
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->when(
                $visibleAccountIds !== null,
                fn ($query) => $query->whereIn('journal_lines.account_id', $visibleAccountIds),
            )
            ->where('journals.ledger_id', $ledger->getKey())
            ->whereIn('journals.status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
            ->whereBetween('journals.journal_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('journal_lines.account_id', 'accounts.number', 'accounts.name')
            ->orderBy('accounts.number')
            ->selectRaw('journal_lines.account_id, accounts.number, accounts.name')
            ->selectRaw('SUM(journal_lines.base_debit) as debit')
            ->selectRaw('SUM(journal_lines.base_credit) as credit')
            ->get();
    }

    /**
     * Whether the ledger's posted entries balance over the range.
     *
     * Only meaningful for an unrestricted viewer: a user who can see part of
     * the chart is looking at part of the trial balance, and part of a balanced
     * set does not balance.
     */
    public function isBalanced(Ledger $ledger, CarbonImmutable $from, CarbonImmutable $to): bool
    {
        $rows = $this->for($ledger, $from, $to);

        $debit = '0';
        $credit = '0';

        foreach ($rows as $row) {
            $debit = bcadd($debit, (string) $row->debit, 6);
            $credit = bcadd($credit, (string) $row->credit, 6);
        }

        return bccomp($debit, $credit, 6) === 0;
    }
}
