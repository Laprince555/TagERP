<?php

namespace Modules\Finance\Livewire\GeneralLedger\JournalBooks;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * JournalBooksTable.
 */
#[Layout('layouts.app')]
class JournalBooksIndex extends Component
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
        $application = app(NavigationTreeService::class)->getApplicationByCode(JournalBook::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(JournalBook::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.journal-books.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
