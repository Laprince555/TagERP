<?php

namespace Modules\HR\Models\EmployeeManagement;

use App\Models\Concerns\ScopedToOrganization;
use App\Models\User;
use App\Support\Code\RecordCodeBuilder;
use App\Support\Organization\EmployeePermissionSynchronizer;
use App\Support\Organization\OrganizationVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Modules\General\Models\World\People\Person;
use Modules\HR\Database\Factories\EmployeeFactory;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\HR\Models\OrganizationStructure\Department;
use Modules\HR\Models\OrganizationStructure\Entity;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * The pivotal record: the only bridge between a logged-in User and the
 * organization structure. Every permission and data-scope check in the
 * system ultimately resolves through this row.
 *
 * @property string $code
 * @property string $slug
 * @property string $entity_scope
 * @property string $department_scope
 * @property string $status
 */
class Employee extends Model
{
    use HasFactory;
    use ScopedToOrganization;
    use SoftDeletes;

    public const APPLICATION_CODE = 'hr-emp-emp';

    protected $fillable = [
        'employee_number',
        'person_id',
        'user_id',
        'entity_id',
        'branch_id',
        'department_id',
        'job_title_id',
        'job_grade_id',
        'entity_scope',
        'department_scope',
        'gross_salary',
        'status',
        'hire_date',
        'termination_date',
    ];

    protected function casts(): array
    {
        return [
            'gross_salary' => 'decimal:2',
            'hire_date' => 'date',
            'termination_date' => 'date',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function jobGrade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($employee->slug)) {
                $employee->slug = $builder->uniqueSlug($employee->employee_number, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($employee->code)) {
                $employee->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $employee->slug);
            }
        });

        // No dedicated cascading UI exists yet to keep entity/branch/
        // department/job_title/job_grade mutually consistent as they're
        // picked (see EmployeeForm), so this is the actual enforcement point
        // — cheap, and it runs on every write regardless of which UI (or
        // script, or tinker session) produced it.
        static::saving(function (Employee $employee): void {
            $employee->assertOrganizationallyConsistent();
        });

        // Reassignment, termination, or reinstatement can all change which
        // scope this employee's own user resolves to, and — since an
        // "entity_tree"/"department_tree" manager's cached scope embeds ids
        // derived from this row — every other cached scope may be stale too.
        static::saved(fn (): mixed => app(OrganizationVersion::class)->bump());
        static::deleted(fn (): mixed => app(OrganizationVersion::class)->bump());

        // Reassignment, promotion, and termination all change which
        // permissions/roles this employee's user should hold — resynced
        // unconditionally rather than only on the fields that changed, since
        // the grant tables themselves can change independently of this row.
        static::saved(fn (Employee $employee): mixed => app(EmployeePermissionSynchronizer::class)->sync($employee));

        // Deleting an employee is an offboarding, not a bookkeeping change:
        // without this the row disappears while their user keeps every
        // permission it was ever granted.
        static::deleted(fn (Employee $employee): mixed => app(EmployeePermissionSynchronizer::class)->sync($employee));
    }

    /**
     * Guards the integrity chain no picker UI enforces on its own: branch
     * belongs to the chosen entity, the job title belongs to the chosen
     * department, and the job grade is one this job title allows.
     *
     * Deliberately does NOT require department_entity to already carry a row
     * for this entity/branch — a department being a shared, group-wide
     * catalog entry, requiring it to be pre-attached here would block
     * legitimate cases (seeding, an entity onboarding a department for the
     * first time via this very employee's hire) on a table whose real job is
     * narrowing the create-form's picker, not gatekeeping every write.
     */
    protected function assertOrganizationallyConsistent(): void
    {
        $branch = $this->branch()->first();

        if ($branch && $branch->entity_id !== $this->entity_id) {
            throw ValidationException::withMessages([
                'branch_id' => 'The selected branch does not belong to the selected entity.',
            ]);
        }

        $jobTitle = $this->jobTitle()->first();

        if ($jobTitle && $jobTitle->department_id !== $this->department_id) {
            throw ValidationException::withMessages([
                'job_title_id' => 'The selected job title does not belong to the selected department.',
            ]);
        }

        if ($jobTitle && ! $jobTitle->jobGrades()->where('job_grades.id', $this->job_grade_id)->exists()) {
            throw ValidationException::withMessages([
                'job_grade_id' => 'The selected job grade is not allowed for the selected job title.',
            ]);
        }
    }

    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }
}
