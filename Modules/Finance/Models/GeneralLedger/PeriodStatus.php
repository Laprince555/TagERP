<?php

namespace Modules\Finance\Models\GeneralLedger;

/**
 * A fiscal period's state within one ledger. Open is the absence of a stored
 * status, so a freshly generated year needs no rows at all.
 */
enum PeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case PermanentlyClosed = 'permanently_closed';

    public function acceptsPostings(): bool
    {
        return $this === self::Open;
    }

    /**
     * A closed period can be reopened; a permanently closed one cannot.
     */
    public function isReopenable(): bool
    {
        return $this === self::Closed;
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => __('Open'),
            self::Closed => __('Closed'),
            self::PermanentlyClosed => __('Permanently Closed'),
        };
    }
}
