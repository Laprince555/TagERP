<?php

namespace Modules\HR\Livewire\Cycles\Cycles;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The stage-entry grid for one Cycle: sequence, approver (job title +
 * optional grade), and the subject-status transitions each stage applies.
 * Mirrors JournalEditor's shape (Finance\Livewire\GeneralLedger\Journals) —
 * the one screen the dynamic table engine can't express because it edits
 * many ordered rows at once — kept local to HR rather than sharing Finance's
 * HasLineRows trait across module boundaries (.ai/rules/module-organization.md).
 */
#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class CycleLinesEditor extends Component
{
    public int $recordId;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public ?string $flash = null;

    public function mount(int $recordId): void
    {
        $this->recordId = $recordId;

        $this->rows = $this->cycle()->lines->map(fn ($line): array => [
            'id' => $line->id,
            'sequence' => $line->sequence,
            'name' => $line->getTranslation('name', 'en'),
            'job_title_id' => $line->job_title_id,
            'job_grade_id' => $line->job_grade_id,
            'target_status_on_approve' => (string) $line->target_status_on_approve,
            'target_status_on_reject' => (string) $line->target_status_on_reject,
        ])->all();

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    #[Computed]
    public function cycle(): Cycle
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Cycle::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }

        $cycle = Cycle::with('lines')->find($this->recordId);

        if ($cycle === null) {
            throw new NotFoundHttpException;
        }

        return $cycle;
    }

    public function isEditable(): bool
    {
        return (bool) auth()->user()?->can(Cycle::APPLICATION_CODE.'.update');
    }

    /**
     * @return Collection<int, JobTitle>
     */
    #[Computed]
    public function jobTitleOptions(): Collection
    {
        return JobTitle::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection<int, JobGrade>
     */
    #[Computed]
    public function jobGradeOptions(): Collection
    {
        return JobGrade::query()->where('is_active', true)->orderBy('level')->get(['id', 'name']);
    }

    public function addRow(): void
    {
        $this->rows[] = [
            'id' => null,
            'sequence' => count($this->rows) + 1,
            'name' => '',
            'job_title_id' => null,
            'job_grade_id' => null,
            'target_status_on_approve' => '',
            'target_status_on_reject' => 'rejected',
        ];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);

        $this->rows = array_values($this->rows);

        foreach ($this->rows as $position => $row) {
            $this->rows[$position]['sequence'] = $position + 1;
        }

        if ($this->rows === []) {
            $this->addRow();
        }
    }

    public function moveUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }

        [$this->rows[$index - 1], $this->rows[$index]] = [$this->rows[$index], $this->rows[$index - 1]];
        $this->resequence();
    }

    public function moveDown(int $index): void
    {
        if ($index >= count($this->rows) - 1) {
            return;
        }

        [$this->rows[$index + 1], $this->rows[$index]] = [$this->rows[$index], $this->rows[$index + 1]];
        $this->resequence();
    }

    public function save(): void
    {
        abort_unless($this->isEditable(), 403);

        $this->validate($this->rules(), [], $this->validationAttributes());

        DB::transaction(function (): void {
            $cycle = $this->cycle();
            $keptIds = [];

            foreach ($this->rows as $position => $row) {
                if (blank($row['name']) && blank($row['job_title_id'])) {
                    continue;
                }

                $line = $cycle->lines()->updateOrCreate(['id' => $row['id'] ?? null], [
                    'sequence' => $position + 1,
                    'name' => ['ar' => $row['name'], 'en' => $row['name']],
                    'job_title_id' => $row['job_title_id'],
                    'job_grade_id' => $row['job_grade_id'] ?: null,
                    'target_status_on_approve' => $row['target_status_on_approve'] ?: null,
                    'target_status_on_reject' => $row['target_status_on_reject'] ?: null,
                ]);

                $keptIds[] = $line->id;
            }

            $cycle->lines()->whereNotIn('id', $keptIds ?: [0])->delete();
        });

        unset($this->cycle);
        $this->mount($this->recordId);

        $this->flash = __('Stages saved.');
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Cycle::APPLICATION_CODE);

        return view('hr::livewire.cycles.cycles.lines-editor', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }

    protected function resequence(): void
    {
        foreach ($this->rows as $position => $row) {
            $this->rows[$position]['sequence'] = $position + 1;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'rows' => ['array'],
            'rows.*.name' => ['nullable', 'string', 'max:255'],
            'rows.*.job_title_id' => ['nullable', 'integer', 'exists:job_titles,id'],
            'rows.*.job_grade_id' => ['nullable', 'integer', 'exists:job_grades,id'],
            'rows.*.target_status_on_approve' => ['nullable', 'string', 'max:255'],
            'rows.*.target_status_on_reject' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'rows.*.name' => __('stage name'),
            'rows.*.job_title_id' => __('job title'),
        ];
    }
}
