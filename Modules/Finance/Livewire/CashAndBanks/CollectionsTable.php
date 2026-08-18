<?php

namespace Modules\Finance\Livewire\CashAndBanks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\DateTimeColumn;
use App\Support\DynamicTable\Core\Columns\MoneyColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\CashAndBanks\Collection\CollectionRequest;
use Modules\HR\Models\OrganizationStructure\Entity;

class CollectionsTable extends Table
{
    use ChecksApplicationAccess;

    protected string $tableKey = 'finance-cash-and-banks-collections';

    protected function applicationCode(): string
    {
        return CollectionRequest::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        return CollectionRequest::query()->with('entity');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('number')->sortable()->searchable()->label('Number'),
            RecordReferenceColumn::make('entity')
                ->applicationCode(Entity::APPLICATION_CODE)
                ->relation('entity')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Entity'),
            DateColumn::make('expected_date')->sortable()->label('Expected Date'),
            MoneyColumn::make('amount')->sortable()->label('Amount'),
            TextColumn::make('collection_method')->sortable()->label('Method'),
            TextColumn::make('status')->sortable()->label('Status'),
            DateTimeColumn::make('created_at')->sortable()->label('Created'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('number'),
            TextFilter::make('status'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('expected_date')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.cash-and-banks.collection.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Collection Request');
    }
}
