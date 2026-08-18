<?php

namespace Modules\CRM\Livewire\Customers;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\CRM\Models\Customers\Customer;
use Modules\CRM\Models\Customers\CustomerType;
use Modules\General\Models\World\Companies\Company;

/**
 * Customers index for the "crm-cust-cus" Application
 * (crm.customer-management.customers).
 */
class CustomersTable extends Table
{
    protected string $tableKey = 'crm-customer-management-customers';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Customer::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Customer::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Customer::query()->whereKey(-1);
        }

        return Customer::query()->with(['company']);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            RecordReferenceColumn::make('company')
                ->applicationCode(Company::APPLICATION_CODE)
                ->relation('company')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Company'),
            EnumColumn::make('customer_type')->enum(CustomerType::class)->sortable()->label('Type'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            EnumFilter::make('customer_type')->enum(CustomerType::class),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('code')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'crm.customer-management.customer.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Customer');
    }
}
