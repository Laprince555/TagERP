<?php

namespace Modules\HR\Models\OrganizationStructure;

use App\Support\Code\RecordCodeBuilder;
use App\Support\Organization\OrganizationVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\World\City;
use Modules\HR\Database\Factories\BranchFactory;

/**
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property bool $is_main
 */
class Branch extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'hr-org-brn';

    protected $table = 'branches';

    protected $fillable = [
        'name',
        'entity_id',
        'is_main',
        'city_id',
        'address',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Branch $branch): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($branch->slug)) {
                $branch->slug = $builder->uniqueSlug($branch->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($branch->code)) {
                $branch->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $branch->slug);
            }
        });

        static::saving(function (Branch $branch): void {
            if ($branch->is_main) {
                self::where('entity_id', $branch->entity_id)
                    ->when($branch->exists, fn ($query) => $query->where('id', '!=', $branch->id))
                    ->update(['is_main' => false]);
            }
        });

        static::saved(fn (): mixed => app(OrganizationVersion::class)->bump());
        static::deleted(fn (): mixed => app(OrganizationVersion::class)->bump());
    }

    /**
     * The `saving` hook above demotes the previous main branch before this row
     * is written. Holding both in one transaction is what stops a failed save
     * from leaving an entity with no main branch at all.
     */
    public function save(array $options = []): bool
    {
        return DB::transaction(fn (): bool => parent::save($options));
    }

    protected static function newFactory(): BranchFactory
    {
        return BranchFactory::new();
    }
}
