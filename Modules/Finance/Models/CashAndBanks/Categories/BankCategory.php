<?php

namespace Modules\Finance\Models\CashAndBanks\Categories;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\BankCategoryFactory;

/**
 * Hierarchical categorization for banks. Parent/child hierarchy is global —
 * a bank selects one category in this tree.
 *
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property ?int $parent_id
 */
class BankCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-cbn-cat';

    protected $table = 'bank_categories';

    protected $fillable = [
        'name',
        'parent_id',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Only a leaf accepts banks; a parent exists to roll its children up.
     */
    public function getIsPostableAttribute(): bool
    {
        $childrenCount = $this->attributes['children_count'] ?? null;

        if ($childrenCount === null) {
            $childrenCount = $this->children()->count();
        }

        return (int) $childrenCount === 0;
    }

    /**
     * Every ancestor of this category, nearest parent first.
     *
     * @return array<int, self>
     */
    public function ancestors(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current !== null) {
            $ancestors[] = $current;
            $current = $current->parent;
        }

        return $ancestors;
    }

    protected static function booted(): void
    {
        static::creating(function (BankCategory $category): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($category->slug)) {
                $category->slug = $builder->uniqueSlug($category->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($category->code)) {
                $category->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $category->slug);
            }
        });
    }

    protected static function newFactory(): BankCategoryFactory
    {
        return BankCategoryFactory::new();
    }
}
