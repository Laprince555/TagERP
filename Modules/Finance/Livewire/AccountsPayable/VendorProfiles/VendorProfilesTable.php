<?php

namespace Modules\Finance\Livewire\AccountsPayable\VendorProfiles;

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
use Modules\Finance\Models\AccountsPayable\VendorFinancialRole;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Procurement\Models\Vendors\Vendor;

/**
 * Vendor Profiles index for the "fin-ap-vpr" Application
 * (finance.accounts-payable.vendor-profiles).
 */
class VendorProfilesTable extends Table
{
    protected string $tableKey = 'finance-accounts-payable-vendor-profiles';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return VendorProfile::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(VendorProfile::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return VendorProfile::query()->whereKey(-1);
        }

        return VendorProfile::query()->with(['vendor.company', 'apAccount']);
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('vendor')
                ->applicationCode(Vendor::APPLICATION_CODE)
                ->relation('vendor')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Vendor'),
            EnumColumn::make('financial_role')->enum(VendorFinancialRole::class)->sortable()->label('Financial Role'),
            RecordReferenceColumn::make('apAccount')
                ->applicationCode(Account::APPLICATION_CODE)
                ->relation('apAccount')
                ->variant(RecordReferenceVariant::Tag)
                ->label('AP Account'),
            NumberColumn::make('payment_terms_days')->sortable()->label('Payment Terms'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            EnumFilter::make('financial_role')->enum(VendorFinancialRole::class),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('financial_role')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.accounts-payable.vendor-profile.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Vendor Profile');
    }
}
