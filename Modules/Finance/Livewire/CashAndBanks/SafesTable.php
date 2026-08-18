<?php

namespace Modules\Finance\Livewire\CashAndBanks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;
use Modules\HR\Models\OrganizationStructure\Entity;

class SafesTable extends Table
{
    use ChecksApplicationAccess;

    protected string $tableKey = 'finance-cash-and-banks-safes';

    protected function applicationCode(): string
    {
        return Safe::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        return Safe::query()->with(['entity', 'employee']);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('entity')
                ->applicationCode(Entity::APPLICATION_CODE)
                ->relation('entity')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Entity'),
            RelationColumn::make('employee.display_name')->label('Responsible Person'),
            TextColumn::make('location')->sortable()->searchable()->label('Location'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            TextFilter::make('location'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.cash-and-banks.safe.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Safe');
    }
}
