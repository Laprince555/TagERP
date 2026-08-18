<?php

namespace Modules\HR\Livewire\Cycles\CycleTypes;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\Cycles\CycleType;

/**
 * Index for the "hr-cyc-typ" Application (hr.cycles.cycle-types).
 */
class CycleTypesTable extends Table
{
    protected string $tableKey = 'hr-cycles-cycle-types';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CycleType::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CycleType::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return CycleType::query()->whereKey(-1);
        }

        return CycleType::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            TextColumn::make('application_code')->searchable()->label('Application Code'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            TextFilter::make('application_code'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'hr.cycles.cycle-type.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Cycle Type');
    }
}
