<?php

namespace Modules\Inventory\Models;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\General\Models\Uom;
use Modules\General\System\SubApplication;

class WarehouseLocation extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_locations';

    protected $fillable = [
        'warehouse_id',
        'sub_application_id',
        'level',
        'aisle',
        'shelf',
        'bin',
        'capacity_units',
        'uom_id',
    ];

    protected $casts = [
        'level' => 'integer',
        'capacity_units' => 'decimal:3',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'location_id');
    }

    public function summary(): HasMany
    {
        return $this->hasMany(InventorySummary::class, 'location_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class, 'location_id');
    }

    public function cycleCountLines(): HasMany
    {
        return $this->hasMany(CycleCountLine::class, 'location_id');
    }

    public function subApplication(): BelongsTo
    {
        return $this->belongsTo(SubApplication::class);
    }

    // Get location code (e.g., "A-01-001")
    public function getLocationCodeAttribute(): string
    {
        return "{$this->aisle}-{$this->shelf}-{$this->bin}";
    }

    // Get available stock for an item
    public function getAvailableStock($itemId): int
    {
        $summary = $this->summary()
            ->where('item_id', $itemId)
            ->first();

        if (! $summary) {
            return 0;
        }

        return $summary->qty_on_hand - $summary->qty_allocated;
    }

    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // Not App\Support\Code\HasAutoLineCode: that trait keys the code off a
    // sequential line_number this model doesn't have. A bin location is
    // identified by its physical aisle-shelf-bin path, not an order of
    // entry, so the identifier segment is built from that path instead.
    protected static function booted(): void
    {
        static::creating(function (WarehouseLocation $location): void {
            if (blank($location->code)) {
                $location->code = app(RecordCodeBuilder::class)->subApplicationRecordCode(
                    (string) $location->warehouse?->code,
                    'loc',
                    $location->location_code,
                );
            }

            if (! $location->sub_application_id) {
                $location->sub_application_id = SubApplication::where('code', Warehouse::APPLICATION_CODE.'-loc')->value('id');
            }
        });
    }
}
