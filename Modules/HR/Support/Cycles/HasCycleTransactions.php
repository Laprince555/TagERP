<?php

namespace Modules\HR\Support\Cycles;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\HR\Models\Cycles\CycleTransaction;
use Modules\HR\Models\Cycles\CycleTransactionStatus;

/**
 * Mixed into any Eloquent model that can be the subject of a Cycle approval
 * workflow. Only declares the relation and the blocking check — actually
 * starting/resolving a cycle for the subject is CycleTransactionService's job,
 * kept out of the model to avoid coupling every subject to HR internals.
 */
trait HasCycleTransactions
{
    public function cycleTransactions(): MorphMany
    {
        return $this->morphMany(CycleTransaction::class, 'subject');
    }

    /**
     * True while an approval workflow is still running against this record —
     * callers (e.g. an activation action) are expected to guard on this
     * before letting the subject move forward.
     */
    public function hasBlockingCycle(): bool
    {
        return $this->cycleTransactions()
            ->whereNotIn('status', [CycleTransactionStatus::Approved->value, CycleTransactionStatus::Cancelled->value])
            ->exists();
    }
}
