<?php

namespace Modules\Finance\Livewire\GeneralLedger\JournalBooks;

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\Ledger;

/**
 * Embedded-only: the ledgers a selectively routed book is carried to — the
 * "Carried To" tab on a Journal Book's record view.
 *
 * Only meaningful when the book's scope is Selected; a book scoped to All
 * reaches every secondary ledger without naming any.
 */
class JournalBookLedgersTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-journal-book-ledgers';

    protected ?string $model = Ledger::class;

    protected function query(): Builder
    {
        return Ledger::query()->with('baseCurrency');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Ledger'),
            RelationColumn::make('baseCurrency.code')->label('Currency'),
            BooleanColumn::make('is_primary')->sortable()->label('Primary'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }
}
