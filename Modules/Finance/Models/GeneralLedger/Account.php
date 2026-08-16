<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\AccountFactory;

/**
 * A single account in the group-wide catalog. The parent/child hierarchy is
 * global — a chart selects a subset of this tree rather than rearranging it.
 *
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property string $number
 * @property ?int $parent_id
 */
class Account extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-gl-acc';

    protected $table = 'accounts';

    protected $fillable = [
        'name',
        'number',
        'parent_id',
        'category_id',
        'required_analysis_type',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class, 'category_id');
    }

    public function charts(): BelongsToMany
    {
        return $this->belongsToMany(Chart::class, 'chart_account');
    }

    /**
     * Only a leaf accepts postings; a parent exists to roll its children up.
     *
     * Reads the eager-loaded `children_count` when the caller supplied one, so
     * listing a few hundred accounts stays a single query.
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
     * Every ancestor of this account, nearest parent first.
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
        static::creating(function (Account $account): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($account->slug)) {
                $account->slug = $builder->uniqueSlug($account->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($account->code)) {
                $account->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $account->slug);
            }
        });
    }

    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }
}
