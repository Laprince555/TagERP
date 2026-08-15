<?php

namespace Modules\General\Livewire\World\Timezones;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the seeded "gen-wld-tzn" Application route
 * (general.world.timezones). Thin page shell only — the actual table lives
 * in TimezonesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class TimezonesIndex extends Component
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
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-tzn');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    public function render(): View
    {
        $context = $this->locateApplication('gen-wld-tzn');

        return view('general::livewire.world.timezones.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }

    private function locateApplication(string $code): ?array
    {
        foreach (app(NavigationTreeService::class)->getTreeForUser() as $module) {
            foreach ($module['sub_modules'] ?? [] as $subModule) {
                foreach ($subModule['applications'] ?? [] as $application) {
                    if (($application['code'] ?? null) === $code) {
                        return [
                            'application' => $application,
                            'subModule' => $subModule,
                            'module' => $module,
                        ];
                    }
                }
            }
        }

        return null;
    }
}
