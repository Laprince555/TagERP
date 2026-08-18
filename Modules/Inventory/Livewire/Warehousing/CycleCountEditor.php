<?php

namespace Modules\Inventory\Livewire\Warehousing;

use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Inventory\Models\CycleCount;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The batch count-entry grid: cycle count lines are keyed in as a batch while
 * physically walking the warehouse, so — like JournalEditor for journal
 * lines — this is the one screen the dynamic table engine can't express.
 *
 * Row shape is kept as plain arrays (not models) for the same reason
 * JournalEditor does: a half-typed row costs nothing until Save. This one
 * screen owns its own add/remove/sync instead of pulling in Finance's
 * HasLineRows trait — Modules\Finance\Livewire\Concerns is Finance-internal,
 * and there's exactly one consumer here, so a shared trait would be the
 * unrequested abstraction ponytail rung 1 rules out.
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class CycleCountEditor extends Component
{
    public int $recordId;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public ?string $flash = null;

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;

        $cycleCount = $this->cycleCount();

        $this->rows = $cycleCount->lines->map(fn ($line): array => [
            'id' => $line->id,
            'item_id' => $line->item_id,
            'location_id' => $line->location_id,
            'system_qty' => (string) $line->system_qty,
            'physical_qty' => $line->physical_qty === null ? '' : (string) $line->physical_qty,
        ])->all();

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    protected function cycleCount(): CycleCount
    {
        $cycleCount = CycleCount::with('lines')->find($this->recordId);

        if ($cycleCount === null) {
            throw new NotFoundHttpException;
        }

        return $cycleCount;
    }

    public function isEditable(): bool
    {
        return auth()->user()?->can(CycleCount::APPLICATION_CODE.'.update')
            && $this->cycleCount()->status === 'in_progress';
    }

    public function addRow(): void
    {
        $this->rows[] = [
            'id' => null,
            'item_id' => null,
            'location_id' => null,
            'system_qty' => '0',
            'physical_qty' => '',
        ];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);

        $this->rows = array_values($this->rows);

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    public function saveDraft(): void
    {
        abort_unless((bool) auth()->user()?->can(CycleCount::APPLICATION_CODE.'.update'), 403);

        if (! $this->isEditable()) {
            $this->flash = __('This cycle count is not in progress and can no longer be counted.');

            return;
        }

        $this->validate([
            'rows' => ['array'],
            'rows.*.item_id' => ['required', 'integer'],
            'rows.*.location_id' => ['required', 'integer', 'exists:warehouse_locations,id'],
            'rows.*.system_qty' => ['required', 'numeric', 'min:0'],
            'rows.*.physical_qty' => ['nullable', 'numeric', 'min:0'],
        ], [], [
            'rows.*.item_id' => __('item'),
            'rows.*.location_id' => __('location'),
        ]);

        DB::transaction(function (): void {
            $cycleCount = $this->cycleCount();

            $keptIds = [];

            foreach ($this->rows as $row) {
                if (blank($row['item_id']) && blank($row['location_id'])) {
                    continue;
                }

                $line = $cycleCount->lines()->updateOrCreate(['id' => $row['id'] ?? null], [
                    'item_id' => $row['item_id'],
                    'location_id' => $row['location_id'],
                    'system_qty' => $row['system_qty'] ?: '0',
                    'physical_qty' => $row['physical_qty'] === '' ? null : $row['physical_qty'],
                ]);

                $keptIds[] = $line->getKey();
            }

            $cycleCount->lines()->whereNotIn('id', $keptIds ?: [0])->delete();
        });

        $this->mount($this->recordId);

        $this->flash = __('Counts saved.');
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(CycleCount::APPLICATION_CODE);

        return view('inventory::livewire.warehousing.cycle-counts.editor', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
