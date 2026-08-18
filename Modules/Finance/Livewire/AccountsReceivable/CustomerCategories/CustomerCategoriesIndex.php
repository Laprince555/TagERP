<?php

namespace Modules\Finance\Livewire\AccountsReceivable\CustomerCategories;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\AccountsReceivable\CustomerCategory;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * CustomerCategoriesTable.
 */
#[Layout('layouts.app')]
class CustomerCategoriesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CustomerCategory::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(CustomerCategory::APPLICATION_CODE);

        return view('finance::livewire.accounts-receivable.customer-categories.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
