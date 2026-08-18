<?php

namespace Modules\Finance\Models\Tax;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\TaxFactory;

/**
 * A named tax (VAT, WHT, ...) with its rate and whether it adds to or is
 * withheld from a document's total.
 *
 * @property string $code
 * @property string $name
 * @property string $direction
 * @property string $rate
 */
class Tax extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-tax-tax';

    protected $table = 'finance_taxes';

    protected $fillable = [
        'name',
        'tax_category_id',
        'direction',
        'rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class, 'tax_category_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Tax $tax): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($tax->slug)) {
                $tax->slug = $builder->uniqueSlug($tax->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($tax->code)) {
                $tax->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $tax->slug);
            }
        });
    }

    protected static function newFactory(): TaxFactory
    {
        return TaxFactory::new();
    }
}
