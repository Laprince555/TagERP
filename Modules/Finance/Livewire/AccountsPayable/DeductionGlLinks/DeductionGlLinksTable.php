<?php

namespace Modules\Finance\Livewire\AccountsPayable\DeductionGlLinks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;
use Modules\Finance\Models\AccountsPayable\DeductionGlLink;
use Modules\Finance\Models\GeneralLedger\Account;

/**
 * Deduction GL Links index for the "fin-ap-dgl" Application
 * (finance.accounts-payable.deduction-gl-links).
 */
class DeductionGlLinksTable extends Table
{
    protected string $tableKey = 'finance-accounts-payable-deduction-gl-links';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return DeductionGlLink::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(DeductionGlLink::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return DeductionGlLink::query()->whereKey(-1);
        }

        return DeductionGlLink::query()->with(['deductionCategory', 'deduction', 'account']);
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('deductionCategory')
                ->applicationCode(DeductionCategory::APPLICATION_CODE)
                ->relation('deductionCategory')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Deduction Category'),
            RecordReferenceColumn::make('deduction')
                ->applicationCode(Deduction::APPLICATION_CODE)
                ->relation('deduction')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Deduction'),
            RecordReferenceColumn::make('account')
                ->applicationCode(Account::APPLICATION_CODE)
                ->relation('account')
                ->variant(RecordReferenceVariant::Tag)
                ->label('GL Account'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('id')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.accounts-payable.deduction-gl-link.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Deduction GL Link');
    }
}
