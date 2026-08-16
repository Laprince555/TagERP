<?php

namespace Modules\Finance\Livewire\GeneralLedger\AccountCategories;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\AccountCategory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page shell for the seeded "fin-gl-cat" Application route. The table itself
 * lives in AccountCategoriesTable.
 */
#[Layout('layouts.app')]
class AccountCategoriesIndex extends Component
{
    public function boot(): void
    {
        $this->checkAccess();
    }

    public function hydrate(): void
    {
        $this->checkAccess();
    }

    protected function checkAccess(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(AccountCategory::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
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
