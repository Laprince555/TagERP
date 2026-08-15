<?php

namespace Modules\HR\Livewire\OrganizationStructure\Entities;

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
use Modules\HR\Models\OrganizationStructure\Entity;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Real production Entities index for the "hr-org-ent" Application
 * (hr.organization-structure.entities), reusing the Dynamic Table engine.
 */
class EntitiesTable extends Table
{
    protected string $tableKey = 'hr-organization-structure-entities';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode(Entity::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Entity::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Entity::query()->whereRaw('1 = 0');
        }

        return Entity::query();
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            RecordReferenceColumn::make('company')
                ->applicationCode('gen-wld-com')
                ->relation('company')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Company'),
            RecordReferenceColumn::make('parent')
                ->applicationCode('hr-org-ent')
                ->relation('parent')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Parent Entity'),
            BooleanColumn::make('is_holding')->sortable()->label('Holding'),
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
        return 'hr.organization-structure.entity.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Entity');
    }
}
