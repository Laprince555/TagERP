<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleLine;
use Modules\HR\Models\Cycles\CycleTransactionLineStatus;
use Modules\HR\Models\Cycles\CycleTransactionStatus;
use Modules\HR\Models\Cycles\CycleType;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\HR\Services\Cycles\CycleTransactionService;

/**
 * A throwaway table/model standing in for "some other module's document" —
 * the engine is generic over any Eloquent model with a `status` column, so a
 * minimal one is built here rather than reusing Employee (whose own `status`
 * column is constrained to its own HR lifecycle values).
 */
class CycleDummySubject extends Model
{
    protected $table = 'cycle_dummy_subjects';

    protected $fillable = ['status'];

    public $timestamps = false;
}

/**
 * The engine's core happy path: a 2-stage cycle runs against a subject,
 * each stage is approved in order by its assigned approver, and the
 * transaction/subject end up in the right final state.
 */
test('a cycle transaction is approved once every stage approves in order', function (): void {
    Schema::create('cycle_dummy_subjects', function (Blueprint $table): void {
        $table->id();
        $table->string('status')->nullable();
    });

    $service = app(CycleTransactionService::class);

    $cycleType = CycleType::factory()->create(['application_code' => 'hr-emp-emp']);

    $cycle = Cycle::factory()->for($cycleType)->create([
        'subject_model' => CycleDummySubject::class,
        'document_type_value' => null,
    ]);

    // Employee::factory() builds a coherent entity/branch/department/job
    // title/grade chain per employee — reused here instead of standalone
    // JobTitle records so each approver's job_title_id actually matches
    // their own department (Employee::assertOrganizationallyConsistent()).
    $firstApproverUser = User::factory()->create();
    $firstApprover = Employee::factory()->create(['user_id' => $firstApproverUser->id]);

    $secondApproverUser = User::factory()->create();
    $secondApprover = Employee::factory()->create(['user_id' => $secondApproverUser->id]);

    $lineOne = CycleLine::factory()->for($cycle)->create([
        'sequence' => 1,
        'job_title_id' => $firstApprover->job_title_id,
        'job_grade_id' => null,
        'target_status_on_approve' => null,
    ]);

    $lineTwo = CycleLine::factory()->for($cycle)->create([
        'sequence' => 2,
        'job_title_id' => $secondApprover->job_title_id,
        'job_grade_id' => null,
        'target_status_on_approve' => 'approved',
    ]);

    $subject = CycleDummySubject::create(['status' => 'pending_approval']);

    $resolved = $service->resolveCycleFor($subject, null);
    expect($resolved?->id)->toBe($cycle->id);

    $transaction = $service->start($subject, $cycle, $firstApproverUser->id);

    expect($transaction->status)->toBe(CycleTransactionStatus::InProgress)
        ->and($transaction->lines)->toHaveCount(2);

    $firstTransactionLine = $transaction->lines()->where('cycle_line_id', $lineOne->id)->firstOrFail();
    $secondTransactionLine = $transaction->lines()->where('cycle_line_id', $lineTwo->id)->firstOrFail();

    $service->act($firstTransactionLine, $firstApproverUser->id, 'approve');

    $firstTransactionLine->refresh();
    $transaction->refresh();

    expect($firstTransactionLine->status)->toBe(CycleTransactionLineStatus::Approved)
        ->and($firstTransactionLine->acted_by)->toBe($firstApproverUser->id)
        ->and($firstTransactionLine->acted_at)->not->toBeNull()
        ->and($transaction->status)->toBe(CycleTransactionStatus::InProgress);

    $service->act($secondTransactionLine, $secondApproverUser->id, 'approve');

    $secondTransactionLine->refresh();
    $transaction->refresh();
    $subject->refresh();

    expect($secondTransactionLine->status)->toBe(CycleTransactionLineStatus::Approved)
        ->and($secondTransactionLine->acted_by)->toBe($secondApproverUser->id)
        ->and($secondTransactionLine->acted_at)->not->toBeNull()
        ->and($transaction->status)->toBe(CycleTransactionStatus::Approved)
        ->and($transaction->completed_at)->not->toBeNull()
        ->and($subject->status)->toBe('approved');
});

/**
 * A subject carrying its own branch_id (e.g. a future org-scoped document)
 * must only be actionable by an approver whose own Employee.branch_id
 * matches — the right job_title alone is not enough. Subjects that don't
 * carry the column (like AP invoices as of this writing) are unaffected by
 * this guard; see CycleTransactionService::organizationMatches().
 */
test('act() rejects an approver whose branch does not match the subject', function (): void {
    Schema::create('cycle_dummy_branch_subjects', function (Blueprint $table): void {
        $table->id();
        $table->string('status')->nullable();
        $table->unsignedBigInteger('branch_id')->nullable();
    });

    $service = app(CycleTransactionService::class);

    $cycleType = CycleType::factory()->create(['application_code' => 'hr-emp-emp']);
    $cycle = Cycle::factory()->for($cycleType)->create([
        'subject_model' => CycleDummyBranchSubject::class,
        'document_type_value' => null,
    ]);

    $approverUser = User::factory()->create();
    $approver = Employee::factory()->create(['user_id' => $approverUser->id]);

    CycleLine::factory()->for($cycle)->create([
        'sequence' => 1,
        'job_title_id' => $approver->job_title_id,
        'job_grade_id' => null,
    ]);

    $otherBranchSubject = CycleDummyBranchSubject::create(['status' => 'pending', 'branch_id' => $approver->branch_id + 1]);
    $mismatchedTransaction = $service->start($otherBranchSubject, $cycle, $approverUser->id);
    $mismatchedLine = $mismatchedTransaction->lines()->firstOrFail();

    expect(fn () => $service->act($mismatchedLine, $approverUser->id, 'approve'))
        ->toThrow(RuntimeException::class, 'not authorized');

    $sameBranchSubject = CycleDummyBranchSubject::create(['status' => 'pending', 'branch_id' => $approver->branch_id]);
    $matchingTransaction = $service->start($sameBranchSubject, $cycle, $approverUser->id);
    $matchingLine = $matchingTransaction->lines()->firstOrFail();

    $service->act($matchingLine, $approverUser->id, 'approve');

    expect($matchingLine->fresh()->status)->toBe(CycleTransactionLineStatus::Approved);
});

/**
 * Standalone dummy for the branch-scope test — kept separate from
 * CycleDummySubject above so that model's lack of a branch_id column stays
 * the representative "no org concept" case the engine must also tolerate.
 */
class CycleDummyBranchSubject extends Model
{
    protected $table = 'cycle_dummy_branch_subjects';

    protected $fillable = ['status', 'branch_id'];

    public $timestamps = false;
}
