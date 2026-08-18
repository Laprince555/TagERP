<?php

namespace Modules\Finance\Livewire\CashAndBanks\InternalAdjust;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\CashAndBanks\InternalAdjust\InternalAdjust;

#[Layout('layouts.app')]
class InternalAdjustsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return InternalAdjust::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(InternalAdjust::APPLICATION_CODE);

        return view('finance::livewire.cash-and-banks.internal-adjust.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
