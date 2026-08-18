<?php

namespace Modules\Finance\Models\AccountsReceivable;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\CustomerCategoryFactory;

/**
 * A customer classification (local, foreign, non-profit, ...) used by AR GL
 * Routing rules to pick a control account — separate from CRM's
 * CustomerType enum, which is the customer's own general classification.
 *
 * @property string $code
 * @property string $name
 */
class CustomerCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-ar-cct';

    protected $table = 'finance_ar_customer_categories';

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
        static::creating(function (CustomerCategory $category): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($category->slug)) {
                $category->slug = $builder->uniqueSlug($category->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($category->code)) {
                $category->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $category->slug);
            }
        });
    }

    protected static function newFactory(): CustomerCategoryFactory
    {
        return CustomerCategoryFactory::new();
    }
}
