<?php

namespace Modules\HR\Models\OrganizationStructure;

use App\Support\Code\RecordCodeBuilder;
use App\Support\Organization\OrganizationVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\Currency;
use Modules\HR\Database\Factories\EntityFactory;

/**
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property string|null $path
 */
class Entity extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'hr-org-ent';

    protected $table = 'entities';

    protected $fillable = [
        'name',
        'company_id',
        'parent_entity_id',
        'is_holding',
        'currency_id',
        'fiscal_year_start_month',
        'fiscal_year_start_day',
        'legal_form',
        'ownership_percentage',
        'tax_authority',
        'board_members',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_holding' => 'boolean',
            'is_active' => 'boolean',
            'board_members' => 'array',
            'ownership_percentage' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_entity_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_entity_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * All entities at or below this one, including itself, resolved from the
     * materialized path with a single indexed LIKE — no recursive query.
     */
    public function scopeDescendantsAndSelf($query)
    {
        return $query->where(function ($q) {
            $q->where('id', $this->id)->orWhere('path', 'like', $this->path.$this->id.'/%');
        });
    }

    protected static function booted(): void
    {
        static::creating(function (Entity $entity): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($entity->slug)) {
                $entity->slug = $builder->uniqueSlug($entity->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($entity->code)) {
                $entity->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $entity->slug);
            }

            $entity->refreshPath();
        });

        static::updating(function (Entity $entity): void {
            if ($entity->isDirty('parent_entity_id')) {
                $entity->refreshPath();
            }
        });

        static::updated(function (Entity $entity): void {
            if ($entity->wasChanged('path')) {
                $entity->cascadePathToDescendants();
            }
        });

        static::saved(fn (): mixed => app(OrganizationVersion::class)->bump());
        static::deleted(fn (): mixed => app(OrganizationVersion::class)->bump());
    }

    /**
     * Recomputes path/depth from the (possibly just-changed) parent. Must run
     * before save on create/parent-change so descendants can be cascaded from
     * the now-correct value in the `updated` hook.
     */
    protected function refreshPath(): void
    {
        $parent = $this->parent_entity_id ? self::withTrashed()->find($this->parent_entity_id) : null;

        $this->path = $parent ? $parent->path.$parent->id.'/' : '/';
        $this->depth = $parent ? $parent->depth + 1 : 0;
    }

    /**
     * Re-derives path/depth for every descendant after this entity moved in
     * the tree, so `path` stays authoritative for every row, not just this one.
     */
    protected function cascadePathToDescendants(): void
    {
        foreach ($this->children()->get() as $child) {
            $child->refreshPath();
            $child->saveQuietly();
            $child->cascadePathToDescendants();
        }
    }

    protected static function newFactory(): EntityFactory
    {
        return EntityFactory::new();
    }
}
