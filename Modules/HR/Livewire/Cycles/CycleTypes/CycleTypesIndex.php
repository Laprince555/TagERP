<?php

namespace Modules\HR\Livewire\Cycles\CycleTypes;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\Cycles\CycleType;

#[Layout('layouts.app')]
class CycleTypesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CycleType::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(CycleType::APPLICATION_CODE);

        return view('hr::livewire.cycles.cycle-types.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
