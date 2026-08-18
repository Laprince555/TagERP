<?php

namespace Modules\Finance\Livewire\CashAndBanks\Categories;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\CashAndBanks\Categories\BankCategory;

#[Layout('layouts.app')]
class BankCategoriesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return BankCategory::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(BankCategory::APPLICATION_CODE);

        return view('finance::livewire.cash-and-banks.categories.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
