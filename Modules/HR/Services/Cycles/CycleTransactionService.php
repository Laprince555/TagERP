<?php

namespace Modules\HR\Services\Cycles;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleException;
use Modules\HR\Models\Cycles\CycleTransaction;
use Modules\HR\Models\Cycles\CycleTransactionLine;
use Modules\HR\Models\Cycles\CycleTransactionLineStatus;
use Modules\HR\Models\Cycles\CycleTransactionStatus;
use Modules\HR\Models\EmployeeManagement\Employee;
use RuntimeException;

/**
 * Runs the generic Cycle approval-workflow engine: resolving which template
 * applies to a subject, starting a transaction against it, and recording each
 * stage's approve/reject action.
 */
class CycleTransactionService
{
    /**
     * Finds the Cycle whose subject_model/document_type_value exactly match
     * the given subject. No fallback to a null-type "generic" cycle — an
     * exact match only, no match means no cycle applies.
     */
    public function resolveCycleFor(Model $subject, ?string $documentTypeValue): ?Cycle
    {
        return Cycle::query()
            ->where('subject_model', get_class($subject))
            ->where('document_type_value', $documentTypeValue)
            ->where('is_active', true)
            ->first();
    }

    public function start(Model $subject, Cycle $cycle, int $startedByUserId): CycleTransaction
    {
        return DB::transaction(function () use ($subject, $cycle, $startedByUserId): CycleTransaction {
            $transaction = CycleTransaction::create([
                'cycle_id' => $cycle->id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'status' => CycleTransactionStatus::InProgress,
                'started_by' => $startedByUserId,
                'started_at' => now(),
            ]);

            foreach ($cycle->lines()->get() as $line) {
                CycleTransactionLine::create([
                    'cycle_transaction_id' => $transaction->id,
                    'cycle_line_id' => $line->id,
                    'sequence' => $line->sequence,
                    'name' => $line->getTranslations('name'),
                    'job_title_id' => $line->job_title_id,
                    'job_grade_id' => $line->job_grade_id,
                    'target_status_on_approve' => $line->target_status_on_approve,
                    'target_status_on_reject' => $line->target_status_on_reject,
                    'status' => CycleTransactionLineStatus::Pending,
                ]);
            }

            return $transaction;
        });
    }

    /**
     * @param  'approve'|'reject'  $action
     */
    public function act(CycleTransactionLine $line, int $userId, string $action, ?CycleException $exception = null, ?string $note = null): void
    {
        if (! in_array($action, ['approve', 'reject'], true)) {
            throw new InvalidArgumentException("Unknown cycle action [{$action}].");
        }

        if ($line->status !== CycleTransactionLineStatus::Pending) {
            throw new RuntimeException("Cycle transaction line [{$line->id}] is no longer pending.");
        }

        $transaction = $line->cycleTransaction;

        if (! $transaction->status->isOpen()) {
            throw new RuntimeException("Cycle transaction [{$transaction->code}] is no longer in progress.");
        }

        if (! $this->userAuthorizedFor($line, $transaction->subject, $userId, $exception)) {
            throw new RuntimeException("User [{$userId}] is not authorized to act on cycle transaction line [{$line->id}].");
        }

        DB::transaction(function () use ($line, $transaction, $userId, $action, $exception, $note): void {
            $line->forceFill([
                'status' => $action === 'approve' ? CycleTransactionLineStatus::Approved : CycleTransactionLineStatus::Rejected,
                'acted_by' => $userId,
                'acted_at' => now(),
                'used_exception_id' => $exception?->id,
                'note' => $note,
            ])->save();

            $subject = $transaction->subject;

            if ($action === 'approve') {
                if (filled($line->target_status_on_approve) && $subject !== null) {
                    $subject->forceFill(['status' => $line->target_status_on_approve])->save();
                }

                $stillPending = $transaction->lines()->where('status', CycleTransactionLineStatus::Pending)->exists();

                if (! $stillPending) {
                    $transaction->forceFill([
                        'status' => CycleTransactionStatus::Approved,
                        'completed_at' => now(),
                    ])->save();
                }

                return;
            }

            // Rejected: the subject goes to its reject status, the whole
            // transaction is rejected, and every stage still pending is
            // closed out as rejected too — CycleTransactionLineStatus has no
            // separate "cancelled"/"skipped" state, and a stage that never
            // ran because the workflow already died is not meaningfully
            // "pending" either, so Rejected is the terminal state used here.
            if (filled($line->target_status_on_reject) && $subject !== null) {
                $subject->forceFill(['status' => $line->target_status_on_reject])->save();
            }

            $transaction->lines()
                ->where('status', CycleTransactionLineStatus::Pending)
                ->where('id', '!=', $line->id)
                ->get()
                ->each(fn (CycleTransactionLine $pending) => $pending->forceFill(['status' => CycleTransactionLineStatus::Rejected])->save());

            $transaction->forceFill([
                'status' => CycleTransactionStatus::Rejected,
                'completed_at' => now(),
            ])->save();
        });
    }

    public function hasBlockingCycle(Model $subject): bool
    {
        return CycleTransaction::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->whereNotIn('status', [CycleTransactionStatus::Approved->value, CycleTransactionStatus::Cancelled->value])
            ->exists();
    }

    /**
     * @param  ?Model  $subject  the transaction's subject, used to enforce
     *                           org-scope (see organizationMatches()) — nullable
     *                           only to tolerate an orphaned subject record
     */
    protected function userAuthorizedFor(CycleTransactionLine $line, ?Model $subject, int $userId, ?CycleException $exception): bool
    {
        $employee = $this->employeeFor($userId);

        if ($employee === null || $employee->job_title_id === null) {
            return false;
        }

        if ($subject !== null && ! $this->organizationMatches($subject, $employee)) {
            return false;
        }

        if ($this->jobTitleGradeMatches($employee, $line->job_title_id, $line->job_grade_id)) {
            return true;
        }

        if ($exception === null || ! $exception->is_active || $exception->cycle_line_id !== $line->id) {
            return false;
        }

        return $this->jobTitleGradeMatches($employee, $exception->job_title_id, $exception->job_grade_id);
    }

    protected function jobTitleGradeMatches(Employee $employee, int $requiredJobTitleId, ?int $requiredJobGradeId): bool
    {
        return $employee->job_title_id === $requiredJobTitleId
            && ($requiredJobGradeId === null || $employee->job_grade_id === $requiredJobGradeId);
    }

    protected function employeeFor(int $userId): ?Employee
    {
        // ponytail: employees are looked up by user_id via the Employee model
        // rather than injecting a resolver interface — the one implementation
        // this engine needs today. Swap for an interface if a second subject
        // domain needs a different "who is this acting user" mapping.
        // withoutGlobalScopes: this is a lookup of who the acting user *is*,
        // not a listing of records they may browse — ScopedToOrganization's
        // scope must not hide someone's own employee row from them.
        return Employee::query()
            ->withoutGlobalScopes()
            ->where('user_id', $userId)
            ->first(['id', 'job_title_id', 'job_grade_id', 'entity_id', 'branch_id', 'department_id']);
    }

    /**
     * Cross-cutting org-scope guard: for every entity_id/branch_id/
     * department_id column the subject's own table actually carries (mirrors
     * OrganizationScopeConstraint's column-presence check), the acting
     * employee must sit in the same one. Subjects with no such column (e.g.
     * AP invoices today) are untouched — this only activates once a subject
     * carries the concept, so no future subject is forced to implement it.
     */
    protected function organizationMatches(Model $subject, Employee $employee): bool
    {
        $columns = Schema::getColumnListing($subject->getTable());

        foreach (['entity_id', 'branch_id', 'department_id'] as $column) {
            if (! in_array($column, $columns, true)) {
                continue;
            }

            $subjectValue = $subject->getAttribute($column);

            if ($subjectValue !== null && $subjectValue !== $employee->getAttribute($column)) {
                return false;
            }
        }

        return true;
    }
}
