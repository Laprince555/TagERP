<?php

namespace Modules\Finance\Models\AccountsPayable;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\DeductionCategoryFactory;

/**
 * The classification of an AP deduction (penalty, retention, advance
 * recovery, ...) used by Deduction GL Links to pick a control account.
 *
 * @property string $code
 * @property string $name
 */
class DeductionCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-ap-dcc';

    protected $table = 'finance_ap_deduction_categories';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DeductionCategory $category): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($category->slug)) {
                $category->slug = $builder->uniqueSlug($category->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($category->code)) {
                $category->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $category->slug);
            }
        });
    }

    protected static function newFactory(): DeductionCategoryFactory
    {
        return DeductionCategoryFactory::new();
    }
}
