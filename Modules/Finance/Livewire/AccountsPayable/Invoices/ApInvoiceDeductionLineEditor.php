<?php

namespace Modules\Finance\Livewire\AccountsPayable\Invoices;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Livewire\Concerns\HasLineRows;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\ApInvoiceDeductionLine;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The deduction-line entry screen for one AP invoice — the "Edit
 * deductions" action on its record view. Same repeating-rows shape as
 * ApInvoiceLineEditor, built on the shared HasLineRows trait.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class ApInvoiceDeductionLineEditor extends Component
{
    use HasLineRows;

    public int $recordId;

    public ?string $flash = null;

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;

        $invoice = $this->invoice();

        $this->rows = $invoice->deductionLines->map(fn (ApInvoiceDeductionLine $line): array => [
            'id' => $line->id,
            'deduction_id' => $line->deduction_id,
            'cost_center_id' => $line->cost_center_id,
            'amount' => (string) $line->amount,
        ])->all();

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    /**
     * @return Collection<int, Deduction>
     */
    #[Computed]
    public function deductionOptions(): Collection
    {
        return Deduction::query()->where('is_active', true)->orderBy('name')->select(['id', 'name'])->get();
    }

    /**
     * @return Collection<int, CostCenter>
     */
    #[Computed]
    public function costCenterOptions(): Collection
    {
        return CostCenter::query()
            ->where('is_active', true)
            ->where('accepts_transactions', true)
            ->whereDoesntHave('children')
            ->orderBy('number')
            ->select(['id', 'number', 'name'])
            ->get();
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
        return ['id' => null, 'deduction_id' => null, 'cost_center_id' => null, 'amount' => '0'];
    }

    public function save(): void
    {
        $this->flash = null;

        $invoice = $this->invoice();

        if (! $invoice->status->isEditable()) {
            $this->flash = __('This invoice is :status and its deduction lines can no longer be edited.', ['status' => $invoice->status->label()]);

            return;
        }

        try {
            $this->syncLineRows(
                $invoice->deductionLines(),
                $this->rows,
                fn (array $row): bool => blank($row['deduction_id']),
                fn (array $row): array => [
                    'deduction_id' => $row['deduction_id'],
                    'cost_center_id' => $row['cost_center_id'] ?: null,
                    'amount' => $row['amount'],
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
        return view('finance::livewire.accounts-payable.invoices.deduction-line-editor', [
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
