<?php

namespace Modules\Finance\Livewire\GeneralLedger\JournalBooks;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\JournalBook;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * JournalBooksTable.
 */
#[Layout('layouts.app')]
class JournalBooksIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return JournalBook::APPLICATION_CODE;
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
