<?php

namespace Modules\Finance\Livewire\AccountsPayable\Invoices;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Livewire\Concerns\HasLineRows;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\ApInvoiceLine;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The line-entry screen for one AP invoice — the "Edit lines" action on its
 * record view. Far simpler than GeneralLedger's JournalEditor: an invoice
 * line is description/quantity/unit_price with no debit/credit balancing,
 * so a plain repeating-rows form is enough; ApInvoiceApprover enforces the
 * subtotal/total agreement at submit time, not here.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class ApInvoiceLineEditor extends Component
{
    use HasLineRows;

    public int $recordId;

    public ?string $flash = null;

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;

        $invoice = $this->invoice();

        $this->rows = $invoice->lines->map(fn (ApInvoiceLine $line): array => [
            'id' => $line->id,
            'description' => $line->description,
            'quantity' => (string) $line->quantity,
            'unit_price' => (string) $line->unit_price,
        ])->all();

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    public function addRow(array $defaults = []): void
    {
        $this->rows[] = $defaults === [] ? $this->emptyRow() : $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyRow(): array
    {
        return ['id' => null, 'description' => '', 'quantity' => '1', 'unit_price' => '0'];
    }

    public function save(): void
    {
        $this->flash = null;

        $invoice = $this->invoice();

        if (! $invoice->status->isEditable()) {
            $this->flash = __('This invoice is :status and its lines can no longer be edited.', ['status' => $invoice->status->label()]);

            return;
        }

        try {
            $this->syncLineRows(
                $invoice->lines(),
                $this->rows,
                fn (array $row): bool => trim((string) $row['description']) === '',
                fn (array $row): array => [
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                ],
            );
        } catch (RuntimeException $exception) {
            $this->flash = $exception->getMessage();

            return;
        }

        $this->redirect(route('finance.accounts-payable.invoices.show', ['recordId' => $invoice->id]));
    }

    public function render(): View
    {
        return view('finance::livewire.accounts-payable.invoices.line-editor', [
            'invoice' => $this->invoice(),
        ]);
    }

    protected function invoice(): ApInvoice
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(ApInvoice::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }

        return ApInvoice::findOrFail($this->recordId);
    }
}
