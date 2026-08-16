<?php

namespace Modules\Finance\Models\GeneralLedger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A period that is no longer open in one specific ledger. Open periods have no
 * row here at all.
 *
 * @property PeriodStatus $status
 */
class LedgerPeriodStatus extends Model
{
    protected $table = 'ledger_period_statuses';

    protected $fillable = [
        'ledger_id',
        'fiscal_period_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => PeriodStatus::class,
        ];
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }
}
