<?php

namespace Modules\Finance\Livewire\GeneralLedger\ExchangeRates;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\RelationColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;
use Modules\Finance\Models\GeneralLedger\RateType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Exchange Rates index for the "fin-gl-rat" Application
 * (finance.general-ledger.exchange-rates).
 */
class ExchangeRatesTable extends Table
{
    protected string $tableKey = 'finance-general-ledger-exchange-rates';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode(ExchangeRate::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(ExchangeRate::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return ExchangeRate::query()->whereRaw('1 = 0');
        }

        return ExchangeRate::query()->with(['fromCurrency', 'toCurrency']);
    }

    protected function columns(): array
    {
        return [
            DateColumn::make('rate_date')->sortable()->label('Date'),
            RelationColumn::make('fromCurrency.code')->label('From'),
            RelationColumn::make('toCurrency.code')->label('To'),
            TextColumn::make('rate')->sortable()->label('Rate'),
            EnumColumn::make('rate_type')
                ->enum(RateType::class)
                ->sortable()
                ->label('Rate Type')
                ->formatUsing(fn (mixed $value): string => $value instanceof RateType ? $value->label() : (string) $value),
        ];
    }

    protected function filters(): array
    {
        return [
            DateFilter::make('rate_date'),
            EnumFilter::make('rate_type')->enum(RateType::class),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('rate_date')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'finance.general-ledger.exchange-rate.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Exchange Rate');
    }
}
