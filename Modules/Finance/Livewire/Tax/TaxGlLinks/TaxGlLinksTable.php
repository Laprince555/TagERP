<?php

namespace Modules\Finance\Livewire\Tax\TaxGlLinks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\BooleanColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxCategory;
use Modules\Finance\Models\Tax\TaxGlLink;

/**
 * Tax GL Links index for the "fin-tax-gll" Application
 * (finance.tax-management.tax-gl-links).
 */
class TaxGlLinksTable extends Table
{
    protected string $tableKey = 'finance-tax-tax-gl-links';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return TaxGlLink::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(TaxGlLink::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return TaxGlLink::query()->whereKey(-1);
        }

        return TaxGlLink::query()->with(['taxCategory', 'tax', 'account']);
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('taxCategory')
                ->applicationCode(TaxCategory::APPLICATION_CODE)
                ->relation('taxCategory')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Tax Category'),
            RecordReferenceColumn::make('tax')
                ->applicationCode(Tax::APPLICATION_CODE)
                ->relation('tax')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Tax'),
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
        return 'finance.tax.tax-gl-link.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Tax GL Link');
    }
}
