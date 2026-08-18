<?php

namespace Modules\HR\Livewire\Cycles\Transactions;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\HR\Models\Cycles\CycleTransaction;
use Modules\HR\Models\Cycles\CycleTransactionLine;
use Modules\HR\Services\Cycles\CycleTransactionService;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Approve/reject screen for one running CycleTransaction, reached from its
 * record view's "Review" action. Every action goes through
 * CycleTransactionService::act(), which owns authorization and the
 * approve/reject state machine — this component only wires the button.
 */
class CycleTransactionReview extends Component
{
    public int $recordId;

    public ?string $note = null;

    public ?string $error = null;

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;
    }

    #[Computed]
    public function transaction(): CycleTransaction
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CycleTransaction::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }

        $transaction = CycleTransaction::with(['lines' => fn ($query) => $query->orderBy('sequence'), 'cycle', 'subject'])
            ->find($this->recordId);

        if ($transaction === null) {
            throw new NotFoundHttpException;
        }

        return $transaction;
    }

    public function approve(int $lineId): void
    {
        $this->act($lineId, 'approve');
    }

    public function reject(int $lineId): void
    {
        $this->act($lineId, 'reject');
    }

    protected function act(int $lineId, string $action): void
    {
        abort_unless((bool) auth()->user()?->can(CycleTransaction::APPLICATION_CODE.'.update'), 403);

        $this->error = null;

        $line = CycleTransactionLine::where('cycle_transaction_id', $this->recordId)->findOrFail($lineId);

        try {
            app(CycleTransactionService::class)->act($line, (int) auth()->id(), $action, null, $this->note ?: null);
            $this->note = null;
        } catch (RuntimeException $e) {
            $this->error = $e->getMessage();
        }

        unset($this->transaction);
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(CycleTransaction::APPLICATION_CODE);

        return view('hr::livewire.cycles.transactions.review', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
