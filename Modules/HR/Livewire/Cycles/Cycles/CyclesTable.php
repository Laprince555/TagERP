<?php

namespace Modules\HR\Livewire\Cycles\Cycles;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleType;

/**
 * Index for the "hr-cyc-cyc" Application (hr.cycles.cycles).
 */
class CyclesTable extends Table
{
    protected string $tableKey = 'hr-cycles-cycles';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Cycle::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Cycle::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Cycle::query()->whereKey(-1);
        }

        return Cycle::query()->with('cycleType');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('cycleType')
                ->applicationCode(CycleType::APPLICATION_CODE)
                ->relation('cycleType')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Cycle Type'),
            TextColumn::make('subject_model')->searchable()->label('Subject Model'),
            TextColumn::make('document_type_value')->searchable()->label('Document Type')->placeholder('Any'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            TextFilter::make('subject_model'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'hr.cycles.cycle.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Cycle');
    }
}
