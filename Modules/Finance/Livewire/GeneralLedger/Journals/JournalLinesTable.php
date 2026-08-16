<?php

namespace Modules\Finance\Livewire\GeneralLedger\Journals;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\JournalLine;

/**
 * Embedded-only: the lines of one journal — the "Lines" tab on a Journal's
 * record view. Never a standalone route, so the parent's query() is the
 * access gate.
 *
 * The analysis is shown from the snapshot columns rather than resolved through
 * the morph: those are what the line was posted with, and they need no join
 * across whichever module the counterparty came from.
 */
class JournalLinesTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-journal-lines';

    protected ?string $model = JournalLine::class;

    protected function query(): Builder
    {
        return JournalLine::query()->with('account');
    }

    protected function columns(): array
    {
        return [
            NumberColumn::make('line_number')->sortable()->label('#'),
            RecordReferenceColumn::make('account')
                ->applicationCode(Account::APPLICATION_CODE)
                ->relation('account')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Account'),
            TextColumn::make('description')->searchable()->label('Description')->placeholder('—'),
            TextColumn::make('analysis_name')->searchable()->label('Analysis')->placeholder('—'),
            MoneyColumn::make('base_debit')->sortable()->label('Debit'),
            MoneyColumn::make('base_credit')->sortable()->label('Credit'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('line_number')->ascending()];
    }
}
