<?php

namespace Modules\General\Livewire;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use Modules\General\System\Application;

/**
 * Ordinary, reusable Dynamic Table for Applications. Works standalone
 * (unconstrained — every Application) and embedded inside a Dynamic Record
 * View (e.g. SubModuleRecordView's "Applications" Other Data tab), where the
 * base Table class layers the parent's relation constraint on top via
 * EmbeddedTableContext (see Table::resolvedQuery()). This class never knows
 * whether it's being embedded or by whom — no parent-specific query()
 * override, so the same class can be reused for any relation whose related
 * model is Application, standalone or embedded any number of times.
 */
class ApplicationsTable extends Table
{
    protected string $tableKey = 'general.applications';

    protected ?string $model = Application::class;

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            BooleanColumn::make('is_active')->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }
}
