<?php

namespace Modules\General\Livewire\System\Users;

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Services\NavigationTreeService;
use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Real production Users index for the "gen-sys-usr" Application
 * (general.system.users), reusing the Dynamic Table engine.
 */
class UsersTable extends Table
{
    protected string $tableKey = 'general-system-users';

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
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-sys-usr');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-sys-usr');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return User::query()->whereRaw('1 = 0');
        }

        return User::query()->with('employee');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable()->searchable()->label('Name'),
            TextColumn::make('email')->sortable()->searchable()->label('Email'),
            RecordReferenceColumn::make('employee')
                ->applicationCode('hr-emp-emp')
                ->relation('employee')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Employee'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            TextFilter::make('email'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'general.system.user.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add User');
    }
}
