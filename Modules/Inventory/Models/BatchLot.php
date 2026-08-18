<?php

namespace Modules\Inventory\Models;

use App\Support\Code\RecordCodeBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchLot extends Model
{
    public const APPLICATION_CODE = 'inv-stk-bat';

    protected $fillable = [
        'batch_number',
        'code',
        'slug',
        'item_id',
        'warehouse_id',
        'expiry_date',
        'cost_per_unit',
        'qty_available',
    ];

    protected $casts = [
        'cost_per_unit' => 'decimal:4',
        'qty_available' => 'decimal:3',
        'expiry_date' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'batch_lot_id');
    }

    // Check if batch is expired
    public function getIsExpiredAttribute(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return Carbon::now()->isAfter($this->expiry_date);
    }

    // Get days until expiry
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    // Scopes
    public function scopeForItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeActive($query)
    {
        return $query->where('qty_available', '>', 0);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', now()->toDateString());
        });
    }

    public function scopeExpiring($query, $daysWarning = 30)
    {
        $date = now()->addDays($daysWarning)->toDateString();

        return $query->where('expiry_date', '<=', $date)
            ->where('expiry_date', '>', now()->toDateString());
    }

    public function scopeOrderByFIFO($query)
    {
        return $query->orderBy('created_at', 'ASC');
    }

    protected static function booted(): void
    {
        static::creating(function (BatchLot $batchLot): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($batchLot->slug)) {
                $batchLot->slug = $builder->uniqueSlug($batchLot->batch_number, fn (string $slug): bool => static::where('slug', $slug)->exists());
            }

            if (blank($batchLot->code)) {
                $batchLot->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $batchLot->slug);
            }
        });
    }
}
