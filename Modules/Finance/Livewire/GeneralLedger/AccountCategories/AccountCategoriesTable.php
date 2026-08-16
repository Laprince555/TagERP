<?php

namespace Modules\Finance\Livewire\GeneralLedger\AccountCategories;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\ComputedColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\AccountCategory;
use Modules\Finance\Models\GeneralLedger\AccountNature;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Account Categories index for the "fin-gl-cat" Application
 * (finance.general-ledger.account-categories).
 */
class AccountCategoriesTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-account-categories';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode(AccountCategory::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(AccountCategory::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return AccountCategory::query()->whereRaw('1 = 0');
        }

        return AccountCategory::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            EnumColumn::make('nature')
                ->enum(AccountNature::class)
                ->sortable()
                ->label('Nature')
                ->formatUsing(fn (mixed $value): string => $value instanceof AccountNature ? $value->label() : (string) $value),
            // Both derive from the nature rather than being stored, so they can
            // never contradict it.
            ComputedColumn::make('normal_balance')
                ->label('Normal Balance')
                ->formatUsing(fn (mixed $value, mixed $row): string => $row->nature->normalBalance()->label()),
            ComputedColumn::make('statement')
                ->label('Statement')
                ->formatUsing(fn (mixed $value, mixed $row): string => $row->nature->statement()->label()),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
            // Backs defaultSort(); categories are read in a deliberate order
            // rather than alphabetically, but the number itself is noise.
            NumberColumn::make('sort_order')->sortable()->hiddenByDefault()->label('Sort Order'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            EnumFilter::make('nature')->enum(AccountNature::class),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('sort_order')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.general-ledger.account-category.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Account Category');
    }
}
