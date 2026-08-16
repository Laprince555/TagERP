<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\AccountCategoryFactory;

/**
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property AccountNature $nature
 */
class AccountCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-gl-cat';

    protected $table = 'account_categories';

    protected $fillable = [
        'name',
        'nature',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'nature' => AccountNature::class,
            'is_active' => 'boolean',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'category_id');
    }

    public function getNormalBalanceAttribute(): NormalBalance
    {
        return $this->nature->normalBalance();
    }

    public function getStatementAttribute(): FinancialStatement
    {
        return $this->nature->statement();
    }

    protected static function booted(): void
    {
        static::creating(function (AccountCategory $category): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($category->slug)) {
                $category->slug = $builder->uniqueSlug($category->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($category->code)) {
                $category->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $category->slug);
            }
        });
    }

    protected static function newFactory(): AccountCategoryFactory
    {
        return AccountCategoryFactory::new();
    }
}
