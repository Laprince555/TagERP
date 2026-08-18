<?php

namespace Modules\HR\Livewire\Cycles\Cycles;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\Cycles\CycleLine;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * Embedded-only: the stages of one Cycle — the "Lines" tab on a Cycle's
 * record view. Never a standalone route; the parent's query() is the access
 * gate. Editing happens on the dedicated CycleLinesEditor screen, reached
 * from the record view's "Edit lines" action.
 */
class CycleLinesTable extends Table
{
    protected string $tableKey = 'hr-cycles-cycle-lines';

    protected ?string $model = CycleLine::class;

    protected function query(): Builder
    {
        return CycleLine::query()->with('jobTitle', 'jobGrade');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('sequence')->sortable()->label('#'),
            TextColumn::make('name')->searchable()->label('Name'),
            RecordReferenceColumn::make('jobTitle')
                ->applicationCode(JobTitle::APPLICATION_CODE)
                ->relation('jobTitle')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Job Title'),
            TextColumn::make('jobGrade.name')->label('Job Grade')->placeholder('Any'),
            TextColumn::make('target_status_on_approve')->label('On Approve')->placeholder('—'),
            TextColumn::make('target_status_on_reject')->label('On Reject')->placeholder('—'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('sequence')->ascending()];
    }
}
