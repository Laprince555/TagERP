<?php

namespace Modules\Inventory\System\StockMovements;

use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\ComputedViewField;
use App\Support\DynamicRecordView\Core\Fields\DateTimeViewField;
use App\Support\DynamicRecordView\Core\Fields\NumberViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\BatchLot;
use Modules\Inventory\Models\InventoryMovement;

/**
 * The authorized record show page for a single InventoryMovement
 * (inv-stk-mov). Append-only audit log: no actions() are declared here — a
 * movement is never edited, deleted, posted, or reversed once written (see
 * InventoryMovement::booted()), so this is purely a browsing surface.
 */
class InventoryMovementRecordView extends DynamicRecordView
{
    protected string $viewKey = 'inventory.stock-movements.movement';

    public function model(): string
    {
        return InventoryMovement::class;
    }

    public function query(): Builder
    {
        return InventoryMovement::query();
    }

    public function title(mixed $record): string
    {
        return (string) $record->code;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->movement_type.' · '.$record->quantity;
    }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')
                ->label('Overview')
                ->default()
                ->contents([
                    FieldsContent::make('movement')
                        ->heading('Movement')
                        ->fields([
                            TextViewField::make('movement_type')->label('Type'),
                            NumberViewField::make('quantity')->label('Quantity'),
                            NumberViewField::make('cost_per_unit')->label('Cost / Unit'),
                            DateTimeViewField::make('created_at')->label('Recorded At'),
                            RelationViewField::make('createdBy.name')->label('Recorded By'),
                        ]),
                    FieldsContent::make('location-and-batch')
                        ->heading('Location & Batch')
                        // Warehouse is only reachable through the location
                        // (InventoryMovement has no direct belongsTo to
                        // Warehouse), and WarehouseLocation itself is not a
                        // registered Application, so it is not eligible for
                        // RecordReferenceViewField (first-level belongsTo to
                        // an Application only) — a plain relation path is the
                        // correct tool here per the RecordReferenceProvider
                        // usage rule.
                        ->fields([
                            RelationViewField::make('location.location_code')->label('Location'),
                            RelationViewField::make('location.warehouse.code')->label('Warehouse'),
                            RecordReferenceViewField::make('batchLot')
                                ->applicationCode(BatchLot::APPLICATION_CODE)
                                ->relation('batchLot')
                                ->label('Batch / Lot'),
                        ]),
                    FieldsContent::make('reference')
                        ->heading('Reference')
                        ->fields([
                            // item_id/uom_id point at tables with no Eloquent
                            // model yet, so they are shown as raw ids rather
                            // than invented relations.
                            TextViewField::make('item_id')->label('Item Id'),
                            TextViewField::make('uom_id')->label('UOM Id'),
                            TextViewField::make('reference_doc_type')->label('Reference Type'),
                            TextViewField::make('reference_doc_id')->label('Reference Id'),
                            ComputedViewField::make('notes')
                                ->label('Notes')
                                ->using(fn (mixed $record): string => $record->notes ?? '—'),
                        ]),
                ]),
        ];
    }
}
