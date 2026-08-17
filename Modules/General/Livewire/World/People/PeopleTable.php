<?php

namespace Modules\General\Livewire\World\People;

use App\Livewire\DynamicTable\Table;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\DateColumn;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Models\World\People\Person;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Real production People index for the "gen-wld-per" Application
 * (general.world.people), reusing the Dynamic Table engine.
 */
class PeopleTable extends Table
{
    protected string $tableKey = 'general-world-people';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode(Person::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        // Re-evaluated on every Livewire render/action, so a disabled
        // Application or revoked permission takes effect immediately —
        // never only at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode(Person::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Person::query()->whereRaw('1 = 0');
        }

        return Person::query()->with('city');
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('name')
                ->applicationCode('gen-wld-per')       // كود التطبيق المرجعي الخاص بالدول
                ->field('full_name')
                ->variant(RecordReferenceVariant::Tag) // اختيار الشكل: Tag
                ->sortable()
                ->searchable()
                ->label('Name'),
            TextColumn::make('national_id')->sortable()->searchable()->label('National ID'),
            RecordReferenceColumn::make('city')
                ->applicationCode('gen-wld-cty')
                ->relation('city')
                ->variant(RecordReferenceVariant::Tag)
                ->label('City'),
            TextColumn::make('phone')->sortable()->searchable()->label('Phone'),
            DateColumn::make('created_at')->hiddenByDefault()->sortable(),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('full_name'),
            TextFilter::make('national_id'),
            DateFilter::make('date_of_birth'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('created_at')->descending()];
    }

    protected function createForm(): ?string
    {
        return 'general.world.person.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Person');
    }
}
