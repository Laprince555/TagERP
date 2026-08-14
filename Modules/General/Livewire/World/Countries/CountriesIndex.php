<?php

namespace Modules\General\Livewire\World\Countries;

use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\General\System\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the seeded "gen-wld-ctr" Application route
 * (general.world.countries). Thin page shell only — the actual table lives
 * in CountriesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class CountriesIndex extends Component
{
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
        $application = Application::query()->where('code', 'gen-wld-ctr')->first();

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    public function render(): View
    {
        return view('general::livewire.world.countries.index');
    }
}
