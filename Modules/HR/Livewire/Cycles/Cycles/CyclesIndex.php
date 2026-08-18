<?php

namespace Modules\HR\Livewire\Cycles\Cycles;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\Cycles\Cycle;

#[Layout('layouts.app')]
class CyclesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Cycle::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Cycle::APPLICATION_CODE);

        return view('hr::livewire.cycles.cycles.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
