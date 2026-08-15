<?php

namespace Modules\HR\Models\OrganizationStructure;

use App\Support\Code\RecordCodeBuilder;
use App\Support\Organization\OrganizationVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HR\Database\Factories\DepartmentFactory;

/**
 * A group-wide catalog definition ("Finance", "Payroll") shared by every
 * entity that uses it, entity-agnostic by design — see entities()/attachToEntity()
 * for which entities/branches actually have it active.
 *
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property string|null $path
 */
class Department extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'hr-org-dep';

    protected $table = 'departments';

    protected $fillable = [
        'name',
        'parent_department_id',
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
        return $this->belongsTo(self::class, 'parent_department_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_department_id');
    }

    public function jobTitles(): HasMany
    {
        return $this->hasMany(JobTitle::class);
    }

    /**
     * Every entity this department is active for, with the attaching
     * branch_id (null = whole entity) available as pivot data.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class, 'department_entity')
            ->withPivot('branch_id')
            ->withTimestamps();
    }

    /**
     * Activates this department for one entity, optionally scoped to a
     * single branch of it (null = the whole entity).
     */
    public function attachToEntity(Entity $entity, ?Branch $branch = null): void
    {
        $this->entities()->attach($entity->id, ['branch_id' => $branch?->id]);
    }

    /**
     * All departments at or below this one, including itself, resolved from
     * the materialized path with a single indexed LIKE — no recursive query.
     */
    public function scopeDescendantsAndSelf($query)
    {
        return $query->where(function ($q) {
            $q->where('id', $this->id)->orWhere('path', 'like', $this->path.$this->id.'/%');
        });
    }

    protected static function booted(): void
    {
        static::creating(function (Department $department): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($department->slug)) {
                $department->slug = $builder->uniqueSlug($department->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($department->code)) {
                $department->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $department->slug);
            }

            $department->refreshPath();
        });

        static::updating(function (Department $department): void {
            if ($department->isDirty('parent_department_id')) {
                $department->refreshPath();
            }
        });

        static::updated(function (Department $department): void {
            if ($department->wasChanged('path')) {
                $department->cascadePathToDescendants();
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
        $parent = $this->parent_department_id ? self::withTrashed()->find($this->parent_department_id) : null;

        $this->path = $parent ? $parent->path.$parent->id.'/' : '/';
        $this->depth = $parent ? $parent->depth + 1 : 0;
    }

    /**
     * Re-derives path/depth for every descendant after this department moved
     * in the tree, so `path` stays authoritative for every row, not just this one.
     */
    protected function cascadePathToDescendants(): void
    {
        foreach ($this->children()->get() as $child) {
            $child->refreshPath();
            $child->saveQuietly();
            $child->cascadePathToDescendants();
        }
    }

    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }
}
