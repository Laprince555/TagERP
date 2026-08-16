<?php

namespace Modules\Finance\Livewire\GeneralLedger\JournalBooks;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Journal Books index for the "fin-gl-bok" Application
 * (finance.general-ledger.journal-books).
 */
class JournalBooksTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-journal-books';

    public function boot(): void
    {
        $this->checkAccess();
    }

    public function hydrate(): void
    {
        $this->checkAccess();
    }

    protected function checkAccess(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(JournalBook::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(JournalBook::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return JournalBook::query()->whereRaw('1 = 0');
        }

        return JournalBook::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
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
