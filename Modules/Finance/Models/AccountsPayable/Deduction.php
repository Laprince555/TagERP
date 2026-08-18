<?php

namespace Modules\Finance\Models\AccountsPayable;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\DeductionFactory;

/**
 * A named AP deduction (penalty, retention, ...) applicable on invoices,
 * either a flat amount or a percentage of the invoice's taxable base.
 *
 * @property string $code
 * @property string $name
 * @property string $calculation_type
 * @property string $value
 */
class Deduction extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-ap-ddc';

    protected $table = 'finance_ap_deductions';

    protected $fillable = [
        'name',
        'deduction_category_id',
        'calculation_type',
        'value',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DeductionCategory::class, 'deduction_category_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Deduction $deduction): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($deduction->slug)) {
                $deduction->slug = $builder->uniqueSlug($deduction->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($deduction->code)) {
                $deduction->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $deduction->slug);
            }
        });
    }

    protected static function newFactory(): DeductionFactory
    {
        return DeductionFactory::new();
    }
}
