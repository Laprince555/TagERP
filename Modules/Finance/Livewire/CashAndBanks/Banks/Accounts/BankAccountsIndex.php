<?php

namespace Modules\Finance\Livewire\CashAndBanks\Banks\Accounts;

use App\Livewire\Concerns\ChecksApplicationAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BankAccountsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        // SubApplication code pattern for bank accounts
        return 'fin-cbn-bnk-bacc';
    }

    public function render(): View
    {
        return view('finance::livewire.cash-and-banks.banks.accounts.index');
    }
}
