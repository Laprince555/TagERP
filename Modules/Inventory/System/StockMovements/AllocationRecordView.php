<?php

namespace Modules\Inventory\System\StockMovements;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\NumberViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\Allocation;

/**
 * The authorized record show page for a single Allocation (inv-stk-alc).
 */
class AllocationRecordView extends DynamicRecordView
{
    protected string $viewKey = 'inventory.stock-movements.allocation';

    public function model(): string
    {
        return Allocation::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Allocation::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Allocation::query()->whereRaw('1 = 0');
        }

        return Allocation::query()->with('location');
    }

    public function title(mixed $record): string
    {
        return $record->code;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->reference_order_type.' #'.$record->reference_order_id;
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
                            NumberViewField::make('item_id')->label('Item ID'),
                            // WarehouseLocation has no APPLICATION_CODE/RecordReferenceProvider
                            // registered as an Application yet — plain relation display.
                            RelationViewField::make('location.bin')->label('Location Bin'),
                            NumberViewField::make('allocated_qty')->decimals(3)->label('Allocated Quantity'),
                            TextViewField::make('reference_order_type')->label('Reference Order Type'),
                            TextViewField::make('reference_order_id')->label('Reference Order Id'),
                            EnumViewField::make('status')
                                ->label('Status')
                                ->labels(['pending' => 'Pending', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled']),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
