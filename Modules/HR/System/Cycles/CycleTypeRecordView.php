<?php

namespace Modules\HR\System\Cycles;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordAction;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\Cycles\CycleType;

/**
 * The authorized record show page for a single CycleType (hr-cyc-typ).
 */
class CycleTypeRecordView extends DynamicRecordView
{
    protected string $viewKey = 'hr.cycles.cycle-type';

    public function model(): string
    {
        return CycleType::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CycleType::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return CycleType::query()->whereRaw('1 = 0');
        }

        return CycleType::query();
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return (string) $record->application_code;
    }

    public function actions(): array
    {
        return [
            RecordAction::make('edit')
                ->label('Edit')
                ->icon('pencil-square')
                ->permission('update')
                ->variant('primary')
                ->form('hr.cycles.cycle-type.create'),
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
                            TextViewField::make('application_code')->label('Application Code'),
                            TextViewField::make('description')->label('Description'),
                            BooleanViewField::make('is_active')->label('Active'),
                        ]),
                ]),
        ];
    }
}
