<?php

namespace Modules\Procurement\System\Vendors;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Models\World\Companies\Company;
use Modules\Procurement\Models\Vendors\Vendor;
use Modules\Procurement\Models\Vendors\VendorType;

/**
 * The authorized record show page for a single Vendor (proc-ven-vnd).
 */
class VendorRecordView extends DynamicRecordView
{
    protected string $viewKey = 'procurement.vendor-management.vendor';

    public function model(): string
    {
        return Vendor::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Vendor::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Vendor::query()->whereRaw('1 = 0');
        }

        return Vendor::query()->with(['company', 'defaultCurrency']);
    }

    public function title(mixed $record): string
    {
        return $record->company->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->vendor_type->label();
    }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')
                ->label('Overview')
                ->default()
                ->contents([
                    FieldsContent::make('basic-information')
                        ->heading('Basic Information')
                        ->fields([
                            RecordReferenceViewField::make('company')
                                ->applicationCode(Company::APPLICATION_CODE)
                                ->relation('company')
                                ->label('Company'),
                            EnumViewField::make('vendor_type')
                                ->label('Vendor Type')
                                ->labels(VendorType::options()),
                            RelationViewField::make('defaultCurrency.name')->label('Default Currency'),
                            BooleanViewField::make('requires_po')->label('Requires Purchase Order'),
                            BooleanViewField::make('is_active')->label('Active'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
