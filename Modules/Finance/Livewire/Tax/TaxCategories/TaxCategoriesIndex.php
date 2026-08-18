<?php

namespace Modules\Finance\Livewire\Tax\TaxCategories;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\Tax\TaxCategory;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * TaxCategoriesTable.
 */
#[Layout('layouts.app')]
class TaxCategoriesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return TaxCategory::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(TaxCategory::APPLICATION_CODE);

        return view('finance::livewire.tax.tax-categories.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
