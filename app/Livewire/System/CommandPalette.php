<?php

namespace App\Livewire\System;

use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\View\View as BladeView;
use Livewire\Component;

class CommandPalette extends Component
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $tree = [];

    public ?string $currentRoute = null;

    public function mount(?array $tree = null, ?NavigationTreeService $service = null): void
    {
        $navigationTreeService = $service ?? app(NavigationTreeService::class);

        $this->tree = $tree ?? $navigationTreeService->getTreeForUser();
        $this->currentRoute = request()->route()?->getName();
    }

    public function render(): View|Factory|BladeView
    {
        return view('livewire.system.command-palette');
    }
}
