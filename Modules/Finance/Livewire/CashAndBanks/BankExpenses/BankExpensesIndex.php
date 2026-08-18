<?php

namespace Modules\Finance\Livewire\CashAndBanks\BankExpenses;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\CashAndBanks\BankExpenses\BankExpense;

#[Layout('layouts.app')]
class BankExpensesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return BankExpense::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(BankExpense::APPLICATION_CODE);

        return view('finance::livewire.cash-and-banks.bank-expenses.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
