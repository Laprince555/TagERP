<?php

namespace Modules\Finance\Livewire\GeneralLedger\Accounts;

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
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\AccountCategory;
use Modules\Finance\Services\GeneralLedger\AccountAccessResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Accounts index for the "fin-gl-acc" Application
 * (finance.general-ledger.accounts).
 */
class AccountsTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-accounts';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode(Account::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Account::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Account::query()->whereRaw('1 = 0');
        }

        return app(AccountAccessResolver::class)->restrict(
            Account::query()->with(['parent', 'category'])
        );
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('number')->sortable()->searchable()->label('Number'),
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('parent')
                ->applicationCode(Account::APPLICATION_CODE)
                ->relation('parent')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Parent'),
            RecordReferenceColumn::make('category')
                ->applicationCode(AccountCategory::APPLICATION_CODE)
                ->relation('category')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Category'),
            // Postability is deliberately absent here: the engine builds its own
            // select list, so any per-row derivation would be one query per row.
            // The record view answers it for a single account instead.
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('number'),
            TextFilter::make('name'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('number')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.general-ledger.account.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Account');
    }
}
