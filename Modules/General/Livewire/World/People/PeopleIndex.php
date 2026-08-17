<?php

namespace Modules\General\Livewire\World\People;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\General\Models\World\People\Person;

/**
 * Reachable page for the seeded "gen-wld-per" Application route
 * (general.world.people). Thin page shell only — the actual table lives
 * in PeopleTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class PeopleIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Person::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Person::APPLICATION_CODE);

        return view('general::livewire.world.people.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
