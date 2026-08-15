<?php

namespace Modules\General\Livewire\World\Companies;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\General\Models\World\Companies\Company;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the seeded "gen-wld-com" Application route
 * (general.world.companies). Thin page shell only — the actual table lives
 * in CompaniesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class CompaniesIndex extends Component
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
        $application = app(NavigationTreeService::class)->getApplicationByCode(Company::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    public function render(): View
    {
        $context = $this->locateApplication(Company::APPLICATION_CODE);

        return view('general::livewire.world.companies.index', [
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
