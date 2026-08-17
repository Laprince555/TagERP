<?php

namespace Modules\General\Livewire\World\Companies;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\General\Models\World\Companies\Company;

/**
 * Reachable page for the seeded "gen-wld-com" Application route
 * (general.world.companies). Thin page shell only — the actual table lives
 * in CompaniesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class CompaniesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Company::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Company::APPLICATION_CODE);

        return view('general::livewire.world.companies.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
