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
use Modules\Finance\Models\AccountsPayable\ApInvoiceTaxLine;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Modules\Finance\Models\Tax\Tax;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The tax-line entry screen for one AP invoice — the "Edit tax lines"
 * action on its record view. Same repeating-rows shape as
 * ApInvoiceLineEditor, built on the shared HasLineRows trait.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class ApInvoiceTaxLineEditor extends Component
{
    use HasLineRows;

    public int $recordId;

    public ?string $flash = null;

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;

        $invoice = $this->invoice();

        $this->rows = $invoice->taxLines->map(fn (ApInvoiceTaxLine $line): array => [
            'id' => $line->id,
            'tax_id' => $line->tax_id,
            'cost_center_id' => $line->cost_center_id,
            'taxable_amount' => (string) $line->taxable_amount,
            'tax_amount' => (string) $line->tax_amount,
        ])->all();

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    /**
     * @return Collection<int, Tax>
     */
    #[Computed]
    public function taxOptions(): Collection
    {
        return Tax::query()->where('is_active', true)->orderBy('name')->select(['id', 'name'])->get();
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
        return ['id' => null, 'tax_id' => null, 'cost_center_id' => null, 'taxable_amount' => '0', 'tax_amount' => '0'];
    }

    public function save(): void
    {
        $this->flash = null;

        $invoice = $this->invoice();

        if (! $invoice->status->isEditable()) {
            $this->flash = __('This invoice is :status and its tax lines can no longer be edited.', ['status' => $invoice->status->label()]);

            return;
        }

        try {
            $this->syncLineRows(
                $invoice->taxLines(),
                $this->rows,
                fn (array $row): bool => blank($row['tax_id']),
                fn (array $row): array => [
                    'tax_id' => $row['tax_id'],
                    'cost_center_id' => $row['cost_center_id'] ?: null,
                    'taxable_amount' => $row['taxable_amount'],
                    'tax_amount' => $row['tax_amount'],
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
        return view('finance::livewire.accounts-payable.invoices.tax-line-editor', [
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
