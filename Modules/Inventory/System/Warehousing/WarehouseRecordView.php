<?php

namespace Modules\Inventory\System\Warehousing;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\NumberViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\Inventory\Livewire\Warehousing\WarehouseLocationsTable;
use Modules\Inventory\Models\Warehouse;

/**
 * The authorized record show page for a single Warehouse (inv-whs-wrh).
 * WarehouseLocation is embedded as a SubApplication tab, same shape as
 * ApInvoiceLinesTable inside ApInvoiceRecordView.
 */
class WarehouseRecordView extends DynamicRecordView
{
    protected string $viewKey = 'inventory.warehousing.warehouse';

    public function model(): string
    {
        return Warehouse::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Warehouse::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Warehouse::query()->whereRaw('1 = 0');
        }

        return Warehouse::query()->with('branch');
    }

    public function title(mixed $record): string
    {
        return $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->code;
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
                            TextViewField::make('code')->label('Code'),
                            TextViewField::make('name')->label('Name'),
                            NumberViewField::make('capacity_m3')->decimals(3)->label('Capacity (m³)'),
                            RecordReferenceViewField::make('branch')
                                ->applicationCode(Branch::APPLICATION_CODE)
                                ->relation('branch')
                                ->label('Branch'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('locations')
                ->applicationKey('inventory.warehousing.warehouse.locations')
                ->label('Locations')
                ->table(WarehouseLocationsTable::class)
                ->relation('locations')
                ->default(),
        ];
    }
}
