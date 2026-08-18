<?php

namespace Modules\Finance\Livewire\CashAndBanks\Banks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;

#[Layout('layouts.app')]
class BanksIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Bank::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Bank::APPLICATION_CODE);

        return view('finance::livewire.cash-and-banks.banks.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
