<?php

namespace Modules\HR\Models\OrganizationStructure;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HR\Database\Factories\JobGradeFactory;

/**
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property int $level
 */
class JobGrade extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'hr-org-jbg';

    protected $table = 'job_grades';

    protected $fillable = [
        'name',
        'level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function jobTitles(): BelongsToMany
    {
        return $this->belongsToMany(JobTitle::class, 'job_title_grade');
    }

    protected static function booted(): void
    {
        static::creating(function (JobGrade $jobGrade): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($jobGrade->slug)) {
                $jobGrade->slug = $builder->uniqueSlug($jobGrade->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($jobGrade->code)) {
                $jobGrade->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $jobGrade->slug);
            }
        });
    }

    protected static function newFactory(): JobGradeFactory
    {
        return JobGradeFactory::new();
    }
}
