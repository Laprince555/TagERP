<?php

namespace Modules\Finance\Livewire\GeneralLedger\AccountGroups;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\AccountGroup;
use Modules\Finance\Models\GeneralLedger\AccountGroupPurpose;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Account Groups index for the "fin-gl-agr" Application
 * (finance.general-ledger.account-groups).
 */
class AccountGroupsTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-account-groups';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode(AccountGroup::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(AccountGroup::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return AccountGroup::query()->whereRaw('1 = 0');
        }

        return AccountGroup::query();
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('name')
                ->applicationCode('fin-gl-agr')
                ->variant(RecordReferenceVariant::Tag)
                ->label('name'),
            EnumColumn::make('purpose')
                ->enum(AccountGroupPurpose::class)
                ->sortable()
                ->label('Purpose')
                ->formatUsing(fn (mixed $value): string => $value instanceof AccountGroupPurpose ? $value->label() : (string) $value),
            TextColumn::make('description')->searchable()->label('Description')->placeholder('-'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            EnumFilter::make('purpose')->enum(AccountGroupPurpose::class),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.general-ledger.account-group.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Account Group');
    }
}
