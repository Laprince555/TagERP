<?php

namespace Modules\Inventory\Models;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\General\System\SubApplication;

class CycleCountLine extends Model
{
    protected $fillable = [
        'cycle_count_id',
        'sub_application_id',
        'code',
        'item_id',
        'location_id',
        'system_qty',
        'physical_qty',
        'adjustment_movement_id',
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'physical_qty' => 'decimal:3',
    ];

    public function cycleCount(): BelongsTo
    {
        return $this->belongsTo(CycleCount::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function adjustmentMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'adjustment_movement_id');
    }

    public function subApplication(): BelongsTo
    {
        return $this->belongsTo(SubApplication::class);
    }

    // Calculate variance (Physical - System)
    public function getVarianceAttribute(): ?float
    {
        if ($this->physical_qty === null) {
            return null;
        }

        return $this->physical_qty - $this->system_qty;
    }

    // Check if variance exists
    public function hasVarianceAttribute(): bool
    {
        return $this->variance !== 0;
    }

    public function scopeForCycleCount($query, $cycleCountId)
    {
        return $query->where('cycle_count_id', $cycleCountId);
    }

    public function scopeWithVariance($query)
    {
        return $query->whereRaw('physical_qty IS NOT NULL AND physical_qty != system_qty');
    }

    public function scopePending($query)
    {
        return $query->whereNull('physical_qty');
    }

    public function scopeCounted($query)
    {
        return $query->whereNotNull('physical_qty');
    }

    // Not App\Support\Code\HasAutoLineCode: unlike JournalLine, this model has
    // no caller-assigned line_number. The identifier is instead the line's
    // 1-based position among its siblings, counted at creation time.
    protected static function booted(): void
    {
        static::creating(function (CycleCountLine $line): void {
            if (blank($line->code)) {
                $lineNumber = static::where('cycle_count_id', $line->cycle_count_id)->count() + 1;

                $line->code = app(RecordCodeBuilder::class)->subApplicationRecordCode(
                    (string) $line->cycleCount?->code,
                    'lin',
                    (string) $lineNumber,
                );
            }

            if (! $line->sub_application_id) {
                $line->sub_application_id = SubApplication::where('code', CycleCount::APPLICATION_CODE.'-lin')->value('id');
            }
        });
    }
}
