<?php

namespace Modules\Inventory\Models;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Allocation extends Model
{
    use SoftDeletes;

    public const APPLICATION_CODE = 'inv-stk-alc';

    protected $fillable = [
        'item_id',
        'location_id',
        'code',
        'slug',
        'allocated_qty',
        'reference_order_type',
        'reference_order_id',
        'status',
    ];

    protected $casts = [
        'allocated_qty' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function scopeForItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFulfilled($query)
    {
        return $query->where('status', 'fulfilled');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'fulfilled'])
            ->whereNull('deleted_at');
    }

    public function scopeForOrder($query, $orderType, $orderId)
    {
        return $query->where('reference_order_type', $orderType)
            ->where('reference_order_id', $orderId);
    }

    protected static function booted(): void
    {
        static::creating(function (Allocation $allocation): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($allocation->slug)) {
                $allocation->slug = $builder->uniqueSlug(
                    'alc-'.uniqid(),
                    fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists(),
                );
            }

            if (blank($allocation->code)) {
                $allocation->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $allocation->slug);
            }
        });
    }
}
