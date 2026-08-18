<?php

namespace Modules\HR\Livewire\Cycles\Transactions;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\Cycles\CycleTransactionLine;
use Modules\HR\Models\Cycles\CycleTransactionLineStatus;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * Embedded-only: the stages of one running CycleTransaction — the "Stages"
 * tab on its record view. Never a standalone route; approve/reject happens on
 * the dedicated CycleTransactionReview screen, reached from the record view.
 */
class CycleTransactionLinesTable extends Table
{
    protected string $tableKey = 'hr-cycles-transaction-lines';

    protected ?string $model = CycleTransactionLine::class;

    protected function query(): Builder
    {
        return CycleTransactionLine::query()->with('jobTitle', 'actedBy');
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
            EnumColumn::make('status')->sortable()->labels(CycleTransactionLineStatus::options())->label('Status'),
            TextColumn::make('actedBy.name')->label('Acted By')->placeholder('—'),
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
