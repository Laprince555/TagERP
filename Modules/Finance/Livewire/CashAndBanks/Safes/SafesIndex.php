<?php

namespace Modules\Finance\Livewire\CashAndBanks\Safes;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;

#[Layout('layouts.app')]
class SafesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Safe::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Safe::APPLICATION_CODE);

        return view('finance::livewire.cash-and-banks.safes.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
