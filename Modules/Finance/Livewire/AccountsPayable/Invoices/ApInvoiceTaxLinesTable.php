<?php

namespace Modules\Finance\Livewire\AccountsPayable\Invoices;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\AccountsPayable\ApInvoiceTaxLine;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Modules\Finance\Models\Tax\Tax;

/**
 * Embedded-only: the tax lines of one AP invoice — the "Tax Lines" tab on
 * an Invoice's record view. Never a standalone route, same shape as
 * ApInvoiceLinesTable.
 */
class ApInvoiceTaxLinesTable extends Table
{
    protected string $tableKey = 'finance-accounts-payable-invoice-tax-lines';

    protected ?string $model = ApInvoiceTaxLine::class;

    protected function query(): Builder
    {
        return ApInvoiceTaxLine::query()->with(['tax', 'costCenter']);
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('tax')
                ->applicationCode(Tax::APPLICATION_CODE)
                ->relation('tax')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Tax'),
            RecordReferenceColumn::make('costCenter')
                ->applicationCode(CostCenter::APPLICATION_CODE)
                ->relation('costCenter')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Cost Center'),
            MoneyColumn::make('taxable_amount')->label('Taxable Amount'),
            MoneyColumn::make('tax_amount')->label('Tax Amount'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('id')->ascending()];
    }
}
