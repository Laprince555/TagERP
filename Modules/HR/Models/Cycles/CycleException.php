<?php

namespace Modules\HR\Models\Cycles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Database\Factories\Cycles\CycleExceptionFactory;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * Who may bypass or delegate one CycleLine's approval, and under what
 * condition (e.g. {"max_amount": 5000}, {"max_hours_delay": 24}).
 *
 * @property string $exception_type
 * @property string $mode
 * @property ?array<string, mixed> $condition
 */
class CycleException extends Model
{
    use HasFactory;

    protected $table = 'cycle_exceptions';

    protected $fillable = [
        'cycle_line_id',
        'job_title_id',
        'job_grade_id',
        'exception_type',
        'condition',
        'mode',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'exception_type' => CycleExceptionType::class,
            'mode' => CycleExceptionMode::class,
            'condition' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function cycleLine(): BelongsTo
    {
        return $this->belongsTo(CycleLine::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function jobGrade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class);
    }

    protected static function newFactory(): CycleExceptionFactory
    {
        return CycleExceptionFactory::new();
    }
}
