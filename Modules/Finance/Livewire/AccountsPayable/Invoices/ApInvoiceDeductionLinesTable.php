<?php

namespace Modules\Finance\Livewire\AccountsPayable\Invoices;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\AccountsPayable\ApInvoiceDeductionLine;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\GeneralLedger\CostCenter;

/**
 * Embedded-only: the deduction lines of one AP invoice — the "Deduction
 * Lines" tab on an Invoice's record view. Never a standalone route, same
 * shape as ApInvoiceLinesTable.
 */
class ApInvoiceDeductionLinesTable extends Table
{
    protected string $tableKey = 'finance-accounts-payable-invoice-deduction-lines';

    protected ?string $model = ApInvoiceDeductionLine::class;

    protected function query(): Builder
    {
        return ApInvoiceDeductionLine::query()->with(['deduction', 'costCenter']);
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('deduction')
                ->applicationCode(Deduction::APPLICATION_CODE)
                ->relation('deduction')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Deduction'),
            RecordReferenceColumn::make('costCenter')
                ->applicationCode(CostCenter::APPLICATION_CODE)
                ->relation('costCenter')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Cost Center'),
            MoneyColumn::make('amount')->label('Amount'),
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
