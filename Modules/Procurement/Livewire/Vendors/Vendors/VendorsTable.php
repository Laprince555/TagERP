<?php

namespace Modules\Procurement\Livewire\Vendors\Vendors;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Models\World\Companies\Company;
use Modules\Procurement\Models\Vendors\Vendor;
use Modules\Procurement\Models\Vendors\VendorType;

/**
 * Vendors index for the "proc-ven-vnd" Application
 * (procurement.vendor-management.vendors).
 */
class VendorsTable extends Table
{
    protected string $tableKey = 'procurement-vendors-vendors';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Vendor::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Vendor::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Vendor::query()->whereKey(-1);
        }

        return Vendor::query()->with('company');
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('company')
                ->applicationCode(Company::APPLICATION_CODE)
                ->relation('company')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Company'),
            EnumColumn::make('vendor_type')->enum(VendorType::class)->sortable()->label('Vendor Type'),
            BooleanColumn::make('requires_po')->sortable()->label('Requires PO'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            EnumFilter::make('vendor_type')->enum(VendorType::class),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('vendor_type')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'procurement.vendor-management.vendor.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Vendor');
    }
}
