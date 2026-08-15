<?php

namespace Modules\General\Livewire\World\Countries;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Nnjeim\World\Models\Country;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Real production Countries index for the "gen-wld-ctr" Application
 * (general.world.countries), reusing the package Country model/table and the
 * Dynamic Table engine. Explicit scalar select/search/sort only — nothing
 * trusts browser-supplied SQL/relations.
 */
class CountriesTable extends Table
{
    protected string $tableKey = 'general-world-countries';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-ctr');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('name')
                ->applicationCode('gen-wld-ctr')       // كود التطبيق المرجعي الخاص بالدول
                ->variant(RecordReferenceVariant::Tag) // اختيار الشكل: Tag
                ->sortable()
                ->searchable()
                ->label('Name'),
            TextColumn::make('iso2')->sortable()->searchable()->label('ISO2'),
            TextColumn::make('phone_code')->sortable()->searchable()->label('Phone Code'),
            TextColumn::make('region')->sortable()->searchable()->label('Region'),
            TextColumn::make('subregion')->sortable()->searchable()->label('Subregion'),
        ];
    }

    /**
     * @return Filter[]
     */
    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            TextFilter::make('region'),
        ];
    }

    protected function query(): Builder
    {
        // Re-evaluated on every Livewire render/action, so a disabled
        // Application or revoked permission takes effect immediately —
        // never only at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-ctr');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Country::query()->whereRaw('1 = 0');
        }

        // Same record-level rule as CountryRecordReferenceProvider::authorize()
        // and CountryRecordView::query(): only active (status = 1) Countries.
        return Country::query()
            ->select(['id', 'name', 'iso2', 'region', 'subregion', 'phone_code', 'status'])
            ->where('status', 1);
    }

    /**
     * @return Sort[]
     */
    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }
}
