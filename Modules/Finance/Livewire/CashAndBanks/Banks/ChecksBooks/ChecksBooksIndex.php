<?php

namespace Modules\Finance\Livewire\CashAndBanks\Banks\ChecksBooks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ChecksBooksIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return 'fin-cbn-bnk-cbk';
    }

    public function render(): View
    {
        return view('finance::livewire.cash-and-banks.banks.checks-books.index');
    }
}
