<?php

namespace Modules\HR\Livewire\Cycles\Transactions;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\Cycles\CycleTransaction;

#[Layout('layouts.app')]
class CycleTransactionsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CycleTransaction::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(CycleTransaction::APPLICATION_CODE);

        return view('hr::livewire.cycles.transactions.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
