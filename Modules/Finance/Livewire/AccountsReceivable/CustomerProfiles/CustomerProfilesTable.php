<?php

namespace Modules\Finance\Livewire\AccountsReceivable\CustomerProfiles;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\CRM\Models\Customers\Customer;
use Modules\Finance\Models\AccountsReceivable\CustomerFinancialRole;
use Modules\Finance\Models\AccountsReceivable\CustomerProfile;
use Modules\Finance\Models\GeneralLedger\Account;

/**
 * Customer Profiles index for the "fin-ar-cpr" Application
 * (finance.accounts-receivable.customer-profiles).
 */
class CustomerProfilesTable extends Table
{
    protected string $tableKey = 'finance-accounts-receivable-customer-profiles';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CustomerProfile::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CustomerProfile::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return CustomerProfile::query()->whereKey(-1);
        }

        return CustomerProfile::query()->with(['customer.company', 'arAccount']);
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('customer')
                ->applicationCode(Customer::APPLICATION_CODE)
                ->relation('customer')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Customer'),
            EnumColumn::make('financial_role')->enum(CustomerFinancialRole::class)->sortable()->label('Financial Role'),
            RecordReferenceColumn::make('arAccount')
                ->applicationCode(Account::APPLICATION_CODE)
                ->relation('arAccount')
                ->variant(RecordReferenceVariant::Tag)
                ->label('AR Account'),
            NumberColumn::make('credit_terms_days')->sortable()->label('Credit Terms'),
            NumberColumn::make('credit_limit')->sortable()->label('Credit Limit'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            EnumFilter::make('financial_role')->enum(CustomerFinancialRole::class),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('financial_role')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.accounts-receivable.customer-profile.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Customer Profile');
    }
}
