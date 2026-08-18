<?php

namespace Modules\Inventory\System\Warehousing;

use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\DateViewField;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordAction;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Livewire\Warehousing\CycleCountLinesTable;
use Modules\Inventory\Models\CycleCount;
use Modules\Inventory\Models\Warehouse;

/**
 * The authorized record show page for a single CycleCount (inv-whs-ccn).
 *
 * Status lifecycle (see CycleCount migration): scheduled -> in_progress ->
 * completed -> variance_applied. Only the transitions the model already
 * supports (the four enum values on the `status` column) get an action here.
 *
 * ponytail: applying the variance only stamps the record variance_applied +
 * approved_by. It does not post InventoryMovement adjustments for the
 * counted lines — that is real stock-posting business logic (an
 * InventoryMovement writer, one per line with a variance) that doesn't exist
 * yet anywhere in the module and is well beyond "the four UI engines". Wire
 * a CycleCountVarianceApplier service into applyVariance() when that's built.
 */
class CycleCountRecordView extends DynamicRecordView
{
    protected string $viewKey = 'inventory.warehousing.cycle-count';

    public function model(): string
    {
        return CycleCount::class;
    }

    public function query(): Builder
    {
        return CycleCount::query();
    }

    public function title(mixed $record): string
    {
        return (string) $record->code;
    }

    public function subtitle(mixed $record): ?string
    {
        return CycleCountStatus::from($record->status)->label().' · '.$record->count_date->toDateString();
    }

    /**
     * Every button gated by one inv-whs-ccn permission each (update / delete
     * / start / complete / apply-variance).
     */
    public function actions(): array
    {
        return [
            RecordAction::make('edit')
                ->label('Edit')
                ->icon('pencil-square')
                ->permission('update')
                ->variant('primary')
                ->form('inventory.warehousing.cycle-count.create')
                ->visible(fn (mixed $record): bool => $this->isScheduled($record)),

            RecordAction::make('count')
                ->label('Enter Counts')
                ->icon('table-cells')
                ->permission('update')
                ->variant('filled')
                ->url(fn (mixed $record): string => route('inventory.warehousing.cycle-counts.edit', ['recordId' => $record->getKey()]))
                ->visible(fn (mixed $record): bool => $record instanceof CycleCount && $record->status === 'in_progress'),

            RecordAction::make('start')
                ->label('Start Counting')
                ->icon('play-circle')
                ->variant('filled')
                ->confirm(__('Start this cycle count? Physical quantities can be entered once it is in progress.'))
                ->successMessage(__('Cycle count started.'))
                ->visible(fn (mixed $record): bool => $this->isScheduled($record)),

            RecordAction::make('complete')
                ->label('Complete')
                ->icon('check-circle')
                ->variant('filled')
                ->confirm(__('Mark this cycle count as completed? The counted quantities will be locked.'))
                ->successMessage(__('Cycle count completed.'))
                ->visible(fn (mixed $record): bool => $record instanceof CycleCount && $record->status === 'in_progress'),

            RecordAction::make('apply-variance')
                ->label('Apply Variance')
                ->icon('scale')
                ->variant('filled')
                ->confirm(__('Apply the counted variance? This is the final step for this cycle count.'))
                ->successMessage(__('Variance applied.'))
                ->visible(fn (mixed $record): bool => $record instanceof CycleCount && $record->status === 'completed'),

            RecordAction::make('delete')
                ->label('Delete')
                ->icon('trash')
                ->variant('danger')
                ->confirm(__('Delete this cycle count? This cannot be undone.'))
                ->visible(fn (mixed $record): bool => $this->isScheduled($record)),
        ];
    }

    public function start(CycleCount $record): void
    {
        $this->writable($record)->forceFill([
            'status' => 'in_progress',
            'counted_by' => auth()->id(),
        ])->save();
    }

    public function complete(CycleCount $record): void
    {
        $this->writable($record)->forceFill(['status' => 'completed'])->save();
    }

    public function applyVariance(CycleCount $record): void
    {
        $this->writable($record)->forceFill([
            'status' => 'variance_applied',
            'approved_by' => auth()->id(),
        ])->save();
    }

    public function delete(Model $record): mixed
    {
        $record->delete();

        return route('inventory.warehousing.cycle-counts');
    }

    /**
     * The instance a record view hands a handler is a display projection —
     * re-fetch the full model before mutating it, same reasoning as
     * JournalRecordView::writable().
     */
    protected function writable(CycleCount $record): CycleCount
    {
        return CycleCount::findOrFail($record->getKey());
    }

    protected function isScheduled(mixed $record): bool
    {
        return $record instanceof CycleCount && $record->status === 'scheduled';
    }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')
                ->label('Overview')
                ->default()
                ->contents([
                    FieldsContent::make('document')
                        ->heading('Document')
                        ->fields([
                            TextViewField::make('code')->label('Code'),
                            RecordReferenceViewField::make('warehouse')
                                ->applicationCode(Warehouse::APPLICATION_CODE)
                                ->relation('warehouse')
                                ->label('Warehouse'),
                            DateViewField::make('count_date')->label('Count Date'),
                            EnumViewField::make('status')
                                ->label('Status')
                                ->labels(CycleCountStatus::options()),
                            RelationViewField::make('countedByUser.name')->label('Counted By'),
                            RelationViewField::make('approvedByUser.name')->label('Approved By'),
                            TextViewField::make('notes')->label('Notes'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('lines')
                ->applicationKey('inventory.warehousing.cycle-count.lines')
                ->label('Lines')
                ->table(CycleCountLinesTable::class)
                ->relation('lines'),
        ];
    }
}
