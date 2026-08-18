<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CycleCount extends Model
{
    public const APPLICATION_CODE = 'inv-whs-ccn';

    protected $fillable = [
        'warehouse_id',
        'code',
        'slug',
        'status',
        'count_date',
        'counted_by',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'count_date' => 'date',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CycleCountLine::class);
    }

    public function countedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    protected static function booted(): void
    {
        static::creating(function (CycleCount $cycleCount): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($cycleCount->slug)) {
                $cycleCount->slug = $builder->uniqueSlug(
                    'cc-'.$cycleCount->count_date?->format('Y-m-d').'-'.uniqid(),
                    fn (string $slug): bool => static::where('slug', $slug)->exists(),
                );
            }

            if (blank($cycleCount->code)) {
                $cycleCount->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $cycleCount->slug);
            }
        });
    }
}
