<?php

namespace Modules\Inventory\System\StockMovements;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\ComputedViewField;
use App\Support\DynamicRecordView\Core\Fields\DateViewField;
use App\Support\DynamicRecordView\Core\Fields\NumberViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\BatchLot;
use Modules\Inventory\Models\Warehouse;

/**
 * The authorized record show page for a single BatchLot (inv-stk-bat).
 */
class BatchLotRecordView extends DynamicRecordView
{
    protected string $viewKey = 'inventory.stock-movements.batch-lot';

    public function model(): string
    {
        return BatchLot::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(BatchLot::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return BatchLot::query()->whereRaw('1 = 0');
        }

        return BatchLot::query()->with('warehouse');
    }

    public function title(mixed $record): string
    {
        return $record->batch_number;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->is_expired ? __('Expired') : null;
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
                            TextViewField::make('batch_number')->label('Batch Number'),
                            NumberViewField::make('item_id')->label('Item ID'),
                            RecordReferenceViewField::make('warehouse')
                                ->applicationCode(Warehouse::APPLICATION_CODE)
                                ->relation('warehouse')
                                ->label('Warehouse'),
                            DateViewField::make('expiry_date')->label('Expiry Date'),
                            NumberViewField::make('cost_per_unit')->decimals(4)->label('Cost Per Unit'),
                            NumberViewField::make('qty_available')->decimals(3)->label('Quantity Available'),
                            ComputedViewField::make('is_expired')
                                ->label('Expired')
                                ->using(fn (mixed $record): string => $record->is_expired ? __('Yes') : __('No')),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
