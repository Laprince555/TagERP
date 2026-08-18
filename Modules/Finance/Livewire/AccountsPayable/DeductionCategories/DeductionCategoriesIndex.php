<?php

namespace Modules\Finance\Livewire\AccountsPayable\DeductionCategories;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * DeductionCategoriesTable.
 */
#[Layout('layouts.app')]
class DeductionCategoriesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return DeductionCategory::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(DeductionCategory::APPLICATION_CODE);

        return view('finance::livewire.accounts-payable.deduction-categories.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
