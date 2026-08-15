<?php

namespace Modules\HR\Models\OrganizationStructure;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HR\Database\Factories\JobTitleFactory;

/**
 * @property string $code
 * @property string $name
 * @property string $slug
 */
class JobTitle extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'hr-org-jbt';

    protected $table = 'job_titles';

    protected $fillable = [
        'name',
        'department_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobGrades(): BelongsToMany
    {
        return $this->belongsToMany(JobGrade::class, 'job_title_grade');
    }

    protected static function booted(): void
    {
        static::creating(function (JobTitle $jobTitle): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($jobTitle->slug)) {
                $jobTitle->slug = $builder->uniqueSlug($jobTitle->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($jobTitle->code)) {
                $jobTitle->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $jobTitle->slug);
            }
        });
    }

    protected static function newFactory(): JobTitleFactory
    {
        return JobTitleFactory::new();
    }
}
