<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySummary extends Model
{
    protected $table = 'inventory_summary';

    protected $fillable = [
        'item_id',
        'location_id',
        'qty_on_hand',
        'qty_allocated',
        'cached_at',
    ];

    protected $casts = [
        'qty_on_hand' => 'decimal:3',
        'qty_allocated' => 'decimal:3',
        'cached_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    // Calculate available stock
    public function getAvailableAttribute(): float
    {
        return max(0, $this->qty_on_hand - $this->qty_allocated);
    }

    // Check if critical stock level
    public function getCriticalAttribute(): bool
    {
        return $this->qty_on_hand <= ($this->item->reorder_point ?? 0);
    }

    public function scopeForItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeLow($query)
    {
        return $query->whereRaw('qty_on_hand <= items.reorder_point')
            ->join('items', 'inventory_summary.item_id', '=', 'items.id');
    }

    public function scopeOversupplied($query)
    {
        return $query->whereRaw('qty_on_hand > items.reorder_qty * 2')
            ->join('items', 'inventory_summary.item_id', '=', 'items.id');
    }
}
