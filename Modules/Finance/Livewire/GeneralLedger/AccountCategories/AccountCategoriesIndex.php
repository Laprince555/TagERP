<?php

namespace Modules\Finance\Livewire\GeneralLedger\AccountCategories;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\AccountCategory;

/**
 * Page shell for the seeded "fin-gl-cat" Application route. The table itself
 * lives in AccountCategoriesTable.
 */
#[Layout('layouts.app')]
class AccountCategoriesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return AccountCategory::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(AccountCategory::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.account-categories.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
