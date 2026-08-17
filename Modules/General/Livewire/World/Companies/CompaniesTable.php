<?php

namespace Modules\General\Livewire\World\Companies;

use App\Livewire\Concerns\ChecksApplicationAccess;
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
use Modules\General\Models\World\Companies\Company;

/**
 * Real production Companies index for the "gen-wld-com" Application
 * (general.world.companies), reusing the Dynamic Table engine.
 */
class CompaniesTable extends Table
{
    protected string $tableKey = 'general-world-companies';

    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Company::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        // Re-evaluated on every Livewire render/action, so a disabled
        // Application or revoked permission takes effect immediately —
        // never only at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode(Company::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Company::query()->whereKey(-1);
        }

        return Company::query()->with('city');
    }

    protected function columns(): array
    {
        return [
            RecordReferenceColumn::make('name')
                ->applicationCode('gen-wld-com')
                ->variant(RecordReferenceVariant::Tag)
                ->label('Name'),
            TextColumn::make('tax_id')->sortable()->searchable()->label('Tax ID'),
            RecordReferenceColumn::make('city')
                ->applicationCode('gen-wld-cty')
                ->relation('city')
                ->variant(RecordReferenceVariant::Tag)
                ->label('City'),
            TextColumn::make('phone')->sortable()->searchable()->label('Phone'),
            BooleanColumn::make('is_active')->sortable()->label('Active'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('name'),
            TextFilter::make('tax_id'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('name')->ascending()];
    }

    protected function createForm(): ?string
    {
        return 'general.world.company.create';
    }

    protected function createFormLabel(): string
    {
        return __('Add Company');
    }
}
