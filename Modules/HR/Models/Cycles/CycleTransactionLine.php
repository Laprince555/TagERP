<?php

namespace Modules\HR\Models\Cycles;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Database\Factories\Cycles\CycleTransactionLineFactory;
use Modules\HR\Models\OrganizationStructure\JobGrade;
use Modules\HR\Models\OrganizationStructure\JobTitle;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * A point-in-time snapshot of one CycleLine plus its actual approve/reject
 * outcome within a running CycleTransaction. cycle_line_id is kept for
 * reference only; every field this stage needs is copied onto this row so
 * later edits to the Cycle template never change a transaction already in
 * flight.
 *
 * @property int $sequence
 * @property string $name
 * @property CycleTransactionLineStatus $status
 */
#[Translatable('name')]
class CycleTransactionLine extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $table = 'cycle_transaction_lines';

    protected $fillable = [
        'cycle_transaction_id',
        'cycle_line_id',
        'sequence',
        'name',
        'job_title_id',
        'job_grade_id',
        'target_status_on_approve',
        'target_status_on_reject',
        'status',
        'acted_by',
        'acted_at',
        'used_exception_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'sequence' => 'integer',
            'status' => CycleTransactionLineStatus::class,
            'acted_at' => 'datetime',
        ];
    }

    public function cycleTransaction(): BelongsTo
    {
        return $this->belongsTo(CycleTransaction::class);
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

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    public function usedException(): BelongsTo
    {
        return $this->belongsTo(CycleException::class, 'used_exception_id');
    }

    protected static function newFactory(): CycleTransactionLineFactory
    {
        return CycleTransactionLineFactory::new();
    }
}
