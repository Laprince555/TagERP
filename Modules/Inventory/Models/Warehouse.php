<?php

namespace Modules\Inventory\Models;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HR\Models\OrganizationStructure\Branch;

class Warehouse extends Model
{
    use SoftDeletes;

    public const APPLICATION_CODE = 'inv-whs-wrh';

    protected $fillable = [
        'branch_id',
        'code',
        'slug',
        'name',
        'capacity_m3',
    ];

    protected $casts = [
        'capacity_m3' => 'decimal:3',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function batchLots(): HasMany
    {
        return $this->hasMany(BatchLot::class);
    }

    public function cycleCounts(): HasMany
    {
        return $this->hasMany(CycleCount::class);
    }

    // Filters by an explicit, caller-supplied branch id — unlike
    // OrganizationScopeResolver (see app/Support/Organization), which resolves
    // an *implicit* multi-dimensional (entity/branch/department) access set
    // for the currently authenticated user via the ScopedToOrganization
    // global scope. That mechanism has exactly one consumer today
    // (Modules/HR/Models/EmployeeManagement/Employee.php), where org
    // dimensions live directly on the employee row. Warehouse->branch_id is a
    // plain foreign key with no such dimensions to resolve, so a direct
    // filter is the correct fit, not a workaround.
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    protected static function booted(): void
    {
        static::creating(function (Warehouse $warehouse): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($warehouse->slug)) {
                $warehouse->slug = $builder->uniqueSlug($warehouse->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($warehouse->code)) {
                $warehouse->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $warehouse->slug);
            }
        });
    }
}
