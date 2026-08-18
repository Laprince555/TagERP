<?php

namespace Modules\Finance\Livewire\CashAndBanks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\DateTimeColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Categories\BankCategory;
use Modules\HR\Models\OrganizationStructure\Entity;

class BanksTable extends Table
{
    use ChecksApplicationAccess;

    protected string $tableKey = 'finance-cash-and-banks-banks';

    protected function applicationCode(): string
    {
        return Bank::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        return Bank::query()->with(['entity', 'category']);
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('code')->sortable()->searchable()->label('Code'),
            TextColumn::make('bank_name')->sortable()->searchable()->label('Bank Name'),
            RecordReferenceColumn::make('entity')
                ->applicationCode(Entity::APPLICATION_CODE)
                ->relation('entity')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Entity'),
            RecordReferenceColumn::make('category')
                ->applicationCode(BankCategory::APPLICATION_CODE)
                ->relation('category')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Category'),
            TextColumn::make('swift_code')->sortable()->label('SWIFT Code'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
            DateTimeColumn::make('created_at')->sortable()->label('Created'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('bank_name'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('bank_name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.cash-and-banks.bank.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Bank');
    }
}
