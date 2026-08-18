<?php

namespace Modules\Finance\Livewire\AccountsPayable\Invoices;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\AccountsPayable\ApInvoiceLine;

/**
 * Embedded-only: the lines of one AP invoice — the "Lines" tab on an
 * Invoice's record view. Never a standalone route, so the parent's query()
 * is the access gate, same shape as JournalLinesTable.
 */
class ApInvoiceLinesTable extends Table
{
    protected string $tableKey = 'finance-accounts-payable-invoice-lines';

    protected ?string $model = ApInvoiceLine::class;

    protected function query(): Builder
    {
        return ApInvoiceLine::query();
    }

    protected function columns(): array
    {
        return [
            NumberColumn::make('line_number')->sortable()->label('#'),
            TextColumn::make('description')->searchable()->label('Description'),
            NumberColumn::make('quantity')->sortable()->label('Quantity'),
            MoneyColumn::make('unit_price')->sortable()->label('Unit Price'),
            MoneyColumn::make('line_total')->sortable()->label('Line Total'),
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
