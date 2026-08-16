<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\CostCenterFactory;

/**
 * @property string $code
 * @property string $name
 * @property string $number
 */
class CostCenter extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-gl-cct';

    protected $table = 'cost_centers';

    protected $fillable = [
        'name',
        'number',
        'parent_id',
        'accepts_transactions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'accepts_transactions' => 'boolean',
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

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'cost_center_id');
    }

    /**
     * A parent exists to roll its children up, so it takes no transactions of
     * its own; a leaf can still be switched off explicitly.
     */
    public function getIsPostableAttribute(): bool
    {
        if (! $this->accepts_transactions || ! $this->is_active) {
            return false;
        }

        return $this->children()->doesntExist();
    }

    protected static function booted(): void
    {
        static::creating(function (CostCenter $costCenter): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($costCenter->slug)) {
                $costCenter->slug = $builder->uniqueSlug($costCenter->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($costCenter->code)) {
                $costCenter->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $costCenter->slug);
            }
        });
    }

    protected static function newFactory(): CostCenterFactory
    {
        return CostCenterFactory::new();
    }
}
