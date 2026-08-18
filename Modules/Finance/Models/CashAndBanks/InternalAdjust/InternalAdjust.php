<?php

namespace Modules\Finance\Models\CashAndBanks\InternalAdjust;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\InternalAdjustFactory;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\General\Models\World\Currency;

/**
 * Internal transfer or adjustment between banks/safes. Creates GL journal
 * entries when posted.
 *
 * @property string $code
 * @property string $slug
 * @property ?string $number
 * @property ?int $from_bank_id
 * @property ?int $from_safe_id
 * @property ?int $to_bank_id
 * @property ?int $to_safe_id
 * @property string $status
 */
class InternalAdjust extends Model
{
    use HasFactory;

    public const APPLICATION_CODE = 'fin-cbn-iadj';

    protected $table = 'internal_adjusts';

    protected $fillable = [
        'number',
        'from_bank_id',
        'from_safe_id',
        'to_bank_id',
        'to_safe_id',
        'adjustment_date',
        'amount',
        'currency_id',
        'description',
        'reference',
        'status',
        'posted_at',
        'journal_id',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'amount' => 'decimal:6',
            'posted_at' => 'datetime',
        ];
    }

    public function fromBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'from_bank_id');
    }

    public function fromSafe(): BelongsTo
    {
        return $this->belongsTo(Safe::class, 'from_safe_id');
    }

    public function toBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'to_bank_id');
    }

    public function toSafe(): BelongsTo
    {
        return $this->belongsTo(Safe::class, 'to_safe_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    protected static function booted(): void
    {
        static::creating(function (InternalAdjust $adjust): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($adjust->slug)) {
                $adjust->slug = $builder->uniqueSlug(
                    $adjust->description ?? $adjust->reference ?? 'adjust',
                    fn (string $slug): bool => static::where('slug', $slug)->exists()
                );
            }

            if (blank($adjust->code)) {
                $adjust->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $adjust->slug);
            }
        });
    }

    protected static function newFactory(): InternalAdjustFactory
    {
        return InternalAdjustFactory::new();
    }
}
