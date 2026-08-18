<?php

namespace Modules\HR\Models\Cycles;

use App\Models\User;
use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\HR\Database\Factories\Cycles\CycleTransactionFactory;

/**
 * One instance of a Cycle running against a real document (its `subject`).
 *
 * @property string $code
 * @property CycleTransactionStatus $status
 */
class CycleTransaction extends Model
{
    use HasFactory;

    public const APPLICATION_CODE = 'hr-cyc-trx';

    protected $table = 'cycle_transactions';

    protected $fillable = [
        'cycle_id',
        'subject_type',
        'subject_id',
        'status',
        'started_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CycleTransactionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CycleTransactionLine::class)->orderBy('sequence');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    protected static function booted(): void
    {
        static::creating(function (CycleTransaction $transaction): void {
            if (blank($transaction->code)) {
                $builder = app(RecordCodeBuilder::class);
                $transaction->code = $builder->applicationRecordCode(
                    self::APPLICATION_CODE,
                    $builder->uniqueSlug('trx-'.now()->format('Y-m-d').'-'.uniqid(), fn (string $slug): bool => static::where('code', self::APPLICATION_CODE.'-'.$slug)->exists()),
                );
            }
        });
    }

    protected static function newFactory(): CycleTransactionFactory
    {
        return CycleTransactionFactory::new();
    }
}
