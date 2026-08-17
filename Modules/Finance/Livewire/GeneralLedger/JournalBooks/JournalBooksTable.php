<?php

namespace Modules\Finance\Livewire\GeneralLedger\JournalBooks;

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
use Modules\Finance\Models\GeneralLedger\JournalBook;

/**
 * Journal Books index for the "fin-gl-bok" Application
 * (finance.general-ledger.journal-books).
 */
class JournalBooksTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-journal-books';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return JournalBook::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(JournalBook::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return JournalBook::query()->whereKey(-1);
        }

        return JournalBook::query();
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('name')
                ->applicationCode('fin-gl-bok')
                ->variant(RecordReferenceVariant::Tag)
                ->label('name'),
            TextColumn::make('sequence_prefix')->sortable()->searchable()->label('Prefix'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
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

    protected function createForm(): ?string
    {
        return 'finance.general-ledger.journal-book.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Journal Book');
    }
}
