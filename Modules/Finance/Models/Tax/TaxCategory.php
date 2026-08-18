<?php

namespace Modules\Finance\Models\Tax;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\TaxCategoryFactory;
use Modules\General\Models\World\Country;

/**
 * The basic classification of a tax (VAT, WHT, ...), scoped to the country
 * whose tax authority defines it.
 *
 * @property string $code
 * @property string $name
 */
class TaxCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-tax-cat';

    protected $table = 'finance_tax_categories';

    protected $fillable = [
        'name',
        'description',
        'country_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    protected static function booted(): void
    {
        static::creating(function (TaxCategory $category): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($category->slug)) {
                $category->slug = $builder->uniqueSlug($category->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($category->code)) {
                $category->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $category->slug);
            }
        });
    }

    protected static function newFactory(): TaxCategoryFactory
    {
        return TaxCategoryFactory::new();
    }
}
