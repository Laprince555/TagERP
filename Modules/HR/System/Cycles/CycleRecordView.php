<?php

namespace Modules\HR\System\Cycles;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordAction;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Livewire\Cycles\Cycles\CycleLinesTable;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleType;

/**
 * The authorized record show page for a single Cycle (hr-cyc-cyc). Stages
 * (cycle_lines) are shown read-only here — editing/reordering them happens on
 * the dedicated CycleLinesEditor screen, the same "grid screen the dynamic
 * table engine can't express" shape JournalEditor uses for journal_lines.
 */
class CycleRecordView extends DynamicRecordView
{
    protected string $viewKey = 'hr.cycles.cycle';

    public function model(): string
    {
        return Cycle::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Cycle::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Cycle::query()->whereRaw('1 = 0');
        }

        return Cycle::query()->with('cycleType');
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return (string) $record->subject_model;
    }

    public function actions(): array
    {
        return [
            RecordAction::make('edit')
                ->label('Edit')
                ->icon('pencil-square')
                ->permission('update')
                ->variant('primary')
                ->form('hr.cycles.cycle.create'),

            RecordAction::make('edit-lines')
                ->label('Edit stages')
                ->icon('table-cells')
                ->permission('update')
                ->variant('filled')
                ->url(fn (mixed $record): string => route('hr.cycles.cycles.edit-lines', ['recordId' => $record->getKey()])),
        ];
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
                            TextViewField::make('name')->label('Name'),
                            RecordReferenceViewField::make('cycleType')
                                ->applicationCode(CycleType::APPLICATION_CODE)
                                ->relation('cycleType')
                                ->label('Cycle Type'),
                            TextViewField::make('subject_model')->label('Subject Model'),
                            TextViewField::make('document_type_value')->label('Document Type')->placeholder('Any'),
                            TextViewField::make('description')->label('Description'),
                            BooleanViewField::make('is_active')->label('Active'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('stages')
                ->applicationKey('hr.cycles.cycle.stages')
                ->label('Stages')
                ->table(CycleLinesTable::class)
                ->relation('lines')
                ->authorization(true),
        ];
    }
}
