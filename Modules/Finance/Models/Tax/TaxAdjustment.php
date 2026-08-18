<?php

namespace Modules\Finance\Models\Tax;

use App\Models\User;
use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\TaxAdjustmentFactory;

/**
 * A correction against a tax — either a settlement/payment made to the tax
 * authority, or a correction following an inspection/audit result.
 *
 * @property string $code
 * @property string $reason
 * @property string $amount
 */
class TaxAdjustment extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-tax-adj';

    protected $table = 'finance_tax_adjustments';

    protected $fillable = [
        'tax_id',
        'adjustment_date',
        'reason',
        'amount',
        'description',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'immutable_date',
            'amount' => 'decimal:6',
        ];
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::creating(function (TaxAdjustment $adjustment): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($adjustment->code)) {
                $adjustment->code = $builder->applicationRecordCode(self::APPLICATION_CODE, (string) str()->random(8));
            }

            if (blank($adjustment->created_by)) {
                $adjustment->created_by = auth()->id();
            }
        });
    }

    protected static function newFactory(): TaxAdjustmentFactory
    {
        return TaxAdjustmentFactory::new();
    }
}
