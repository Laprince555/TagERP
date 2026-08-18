<?php

namespace Modules\Finance\Livewire\CashAndBanks\Banks\Checks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ChecksIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return 'fin-cbn-bnk-chk';
    }

    public function render(): View
    {
        return view('finance::livewire.cash-and-banks.banks.checks.index');
    }
}
