<?php

namespace Modules\Finance\Livewire\GeneralLedger\JournalBooks;

use App\Livewire\DynamicRecordView\RecordView;
use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the JournalBook show route. Thin adapter only.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class JournalBookRecordView extends RecordView
{
    public function boot(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(JournalBook::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'finance.general-ledger.journal-book';
    }
}
