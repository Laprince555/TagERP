<?php

namespace Modules\General\Livewire\Security\Rules;

use App\Livewire\DynamicTable\Table;
use App\Models\Role;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Real production Rules index for the "gen-sec-rul" Application
 * (general.security.rules), reusing the Dynamic Table engine.
 */
class RulesTable extends Table
{
    protected string $tableKey = 'general-security-rules';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-sec-rul');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-sec-rul');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Role::query()->whereRaw('1 = 0');
        }

        return Role::query();
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('name')
                ->applicationCode('gen-sec-rul')       // كود التطبيق المرجعي الخاص بالدول
                ->variant(RecordReferenceVariant::Tag) // اختيار الشكل: Tag
                ->sortable()
                ->searchable(),
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
        return 'general.security.rule.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Rule');
    }
}
