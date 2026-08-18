<?php

namespace Modules\Finance\Livewire\AccountsPayable\Invoices;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\ApInvoiceStatus;
use Modules\Finance\Models\AccountsPayable\VendorProfile;

/**
 * AP Invoices index for the "fin-ap-inv" Application
 * (finance.accounts-payable.invoices).
 */
class ApInvoicesTable extends Table
{
    protected string $tableKey = 'finance-accounts-payable-invoices';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return ApInvoice::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(ApInvoice::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return ApInvoice::query()->whereKey(-1);
        }

        return ApInvoice::query()->with('vendorProfile.vendor.company');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('invoice_number')->sortable()->searchable()->label('Invoice Number'),
            RecordReferenceColumn::make('vendorProfile')
                ->applicationCode(VendorProfile::APPLICATION_CODE)
                ->relation('vendorProfile')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Vendor Profile'),
            DateColumn::make('issue_date')->sortable()->label('Issue Date'),
            DateColumn::make('due_date')->sortable()->label('Due Date'),
            EnumColumn::make('status')->enum(ApInvoiceStatus::class)->sortable()->label('Status'),
            MoneyColumn::make('total_amount')->sortable()->label('Total'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('invoice_number'),
            DateFilter::make('issue_date'),
            EnumFilter::make('status')->enum(ApInvoiceStatus::class),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('issue_date')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.accounts-payable.invoice.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Invoice');
    }
}
