<?php

namespace Modules\HR\Models\Cycles;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HR\Database\Factories\Cycles\CycleLineFactory;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * One ordered approval stage of a Cycle template: who must approve
 * (job_title, optionally narrowed to job_grade) and what happens to the
 * subject's status on approval/rejection.
 *
 * @property string $code
 * @property int $sequence
 * @property string $name
 */
#[Translatable('name')]
class CycleLine extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $table = 'cycle_lines';

    protected $fillable = [
        'cycle_id',
        'sequence',
        'name',
        'job_title_id',
        'job_grade_id',
        'target_status_on_approve',
        'target_status_on_reject',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'sequence' => 'integer',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function jobGrade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(CycleException::class);
    }

    protected static function booted(): void
    {
        static::creating(function (CycleLine $line): void {
            if (filled($line->code)) {
                return;
            }

            $parent = $line->cycle;

            $line->code = app(RecordCodeBuilder::class)->subApplicationRecordCode(
                (string) $parent?->code,
                'lin',
                (string) $line->sequence,
            );
        });
    }

    protected static function newFactory(): CycleLineFactory
    {
        return CycleLineFactory::new();
    }
}
