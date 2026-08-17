<?php

namespace Modules\HR\Livewire\OrganizationStructure\Entities;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * Reachable page for the seeded "hr-org-ent" Application route
 * (hr.organization-structure.entities). Thin page shell only — the actual
 * table lives in EntitiesTable (App\Livewire\DynamicTable\Table subclass).
 */
#[Layout('layouts.app')]
class EntitiesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Entity::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Entity::APPLICATION_CODE);

        return view('hr::livewire.organization-structure.entities.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
