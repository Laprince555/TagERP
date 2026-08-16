<?php

namespace Modules\Finance\Models\GeneralLedger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Finance\Services\GeneralLedger\AccountAccessResolver;

/**
 * Grants an account group to an employee or to a job title.
 */
class AccountGroupAssignment extends Model
{
    protected $table = 'account_group_assignments';

    protected $fillable = [
        'account_group_id',
        'assignable_type',
        'assignable_id',
    ];

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'account_group_id');
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo('assignable');
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(AccountAccessResolver::class)->flush());
        static::deleted(fn () => app(AccountAccessResolver::class)->flush());
    }
}
