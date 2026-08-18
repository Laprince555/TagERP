<?php

namespace Modules\Finance\Models\AccountsPayable;

/**
 * An AP invoice's place in its approval chain. There is no "edited" state
 * once submitted, mirroring GeneralLedger\JournalStatus: Draft and Rejected
 * are the only states a header or its lines may still change in — a
 * rejection sends the invoice back to be fixed and resubmitted rather than
 * corrected in place mid-approval.
 */
enum ApInvoiceStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Activated = 'activated';

    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::PendingApproval => __('Pending Approval'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::Activated => __('Activated'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
