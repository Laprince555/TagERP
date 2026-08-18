<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\General\Models\Uom;
use RuntimeException;

class InventoryMovement extends Model
{
    public const APPLICATION_CODE = 'inv-stk-mov';

    protected $fillable = [
        'item_id',
        'location_id',
        'code',
        'slug',
        'movement_type',
        'quantity',
        'uom_id',
        'cost_per_unit',
        'reference_doc_type',
        'reference_doc_id',
        'batch_lot_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'cost_per_unit' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    // An inventory movement is an append-only record of something that
    // happened, so it has a creation time but no legitimate "last updated"
    // time; only created_at is hand-stamped below.
    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (InventoryMovement $model): void {
            $model->created_at = now();

            $builder = app(RecordCodeBuilder::class);

            if (blank($model->slug)) {
                $model->slug = $builder->uniqueSlug('mov-'.uniqid(), fn (string $slug): bool => static::where('slug', $slug)->exists());
            }

            if (blank($model->code)) {
                $model->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $model->slug);
            }
        });

        // Append-only: a movement is the historical record other totals
        // (summaries, FIFO/WAC costing) are derived from. Editing or
        // deleting one in place would leave those derived numbers
        // disagreeing with the record they were computed from.
        static::updating(function (InventoryMovement $model): void {
            throw new RuntimeException('An inventory movement is append-only and cannot be modified or deleted.');
        });

        static::deleting(function (InventoryMovement $model): void {
            throw new RuntimeException('An inventory movement is append-only and cannot be modified or deleted.');
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function batchLot(): BelongsTo
    {
        return $this->belongsTo(BatchLot::class, 'batch_lot_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeOfType($query, $type): Builder
    {
        return $query->where('movement_type', $type);
    }

    public function scopeForItem($query, $itemId): Builder
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeForLocation($query, $locationId): Builder
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeForReference($query, $refType, $refId): Builder
    {
        return $query->where('reference_doc_type', $refType)
            ->where('reference_doc_id', $refId);
    }

    public function scopeIncoming($query): Builder
    {
        return $query->where('movement_type', 'IN');
    }

    public function scopeOutgoing($query): Builder
    {
        return $query->where('movement_type', 'OUT');
    }

    // Get cost for FIFO/WAC calculations
    public function getCostAttribute(): float
    {
        return (float) ($this->quantity * $this->cost_per_unit);
    }

    // Calculate quantity sign based on movement type
    public function getSignedQuantityAttribute(): int
    {
        $outbound = ['OUT', 'DEALLOCATION'];
        $sign = in_array($this->movement_type, $outbound) ? -1 : 1;

        return $this->quantity * $sign;
    }
}
