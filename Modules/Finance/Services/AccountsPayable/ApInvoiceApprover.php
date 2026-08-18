<?php

namespace Modules\Finance\Services\AccountsPayable;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\ApInvoiceStatus;
use Modules\HR\Models\Cycles\CycleTransaction;
use Modules\HR\Models\Cycles\CycleTransactionLine;
use Modules\HR\Models\Cycles\CycleTransactionLineStatus;
use Modules\HR\Models\Cycles\CycleTransactionStatus;
use Modules\HR\Services\Cycles\CycleTransactionService;
use RuntimeException;

/**
 * The single gate an AP invoice passes through on its way from draft to
 * activated. Every business rule lives here rather than in the form or the
 * UI, so an import or a future integration is held to the same standard as
 * a clerk clicking through the screen — mirrors GeneralLedger\JournalPoster.
 *
 * The chain is: Draft --submit--> PendingApproval --approve--> Approved
 * --activate--> Activated, or PendingApproval --reject--> Rejected (which
 * can be edited and resubmitted). Activation is deliberately the one step
 * this service does not fold into approve(): it is the named human action
 * that turns an approved invoice into money TagERP will actually pay.
 *
 * When invoice_type_id resolves to a matching HR Cycle, submit()/approve()/
 * reject() hand the invoice off to CycleTransactionService instead of doing
 * the single-step transition themselves — see each method's own comment.
 */
class ApInvoiceApprover
{
    private const SCALE = 6;

    public function __construct(private readonly CycleTransactionService $cycles) {}

    /**
     * @throws RuntimeException when any submission rule is broken
     */
    public function submit(ApInvoice $invoice, ?int $userId = null): ApInvoice
    {
        return DB::transaction(function () use ($invoice, $userId): ApInvoice {
            $invoice->status = $this->lockedStatus($invoice);

            $invoice->loadMissing(['lines', 'vendorProfile.vendor', 'vendorProfile.currency', 'invoiceType']);

            $this->assertSubmittable($invoice);

            $cycle = $this->cycles->resolveCycleFor($invoice, $invoice->invoiceType?->slug);

            if ($cycle !== null) {
                if ($userId === null) {
                    throw new RuntimeException("Invoice [{$invoice->code}] requires a submitting user to start its approval cycle.");
                }

                $this->cycles->start($invoice, $cycle, $userId);
            }

            // start() only snapshots the cycle's lines — it never touches the
            // subject's own status — so PendingApproval is set here either
            // way as the "submitted" marker; once a cycle is running, each
            // stage's own target_status_on_approve (applied by act()) is what
            // actually moves the invoice further from here.
            $invoice->forceFill([
                'status' => ApInvoiceStatus::PendingApproval,
                'submitted_at' => now(),
                'submitted_by' => $userId,
            ])->save();

            return $invoice;
        });
    }

    /**
     * @throws RuntimeException
     */
    public function approve(ApInvoice $invoice, ?int $userId = null): ApInvoice
    {
        return DB::transaction(function () use ($invoice, $userId): ApInvoice {
            $invoice->status = $this->lockedStatus($invoice);

            $activeTransaction = $this->activeCycleTransaction($invoice);

            if ($activeTransaction !== null) {
                $line = $this->currentPendingLine($activeTransaction);

                if ($line === null) {
                    throw new RuntimeException("Invoice [{$invoice->code}] has no pending approval stage left to act on.");
                }

                $this->cycles->act($line, (int) $userId, 'approve');

                return $invoice->fresh();
            }

            if ($invoice->status !== ApInvoiceStatus::PendingApproval) {
                throw new RuntimeException("Only an invoice pending approval can be approved; [{$invoice->code}] is {$invoice->status->label()}.");
            }

            $invoice->forceFill([
                'status' => ApInvoiceStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $userId,
            ])->save();

            return $invoice;
        });
    }

    /**
     * @throws RuntimeException
     */
    public function reject(ApInvoice $invoice, ?int $userId = null, ?string $reason = null): ApInvoice
    {
        return DB::transaction(function () use ($invoice, $userId, $reason): ApInvoice {
            $invoice->status = $this->lockedStatus($invoice);

            $activeTransaction = $this->activeCycleTransaction($invoice);

            if ($activeTransaction !== null) {
                $line = $this->currentPendingLine($activeTransaction);

                if ($line === null) {
                    throw new RuntimeException("Invoice [{$invoice->code}] has no pending approval stage left to act on.");
                }

                $this->cycles->act($line, (int) $userId, 'reject', note: $reason);

                return $invoice->fresh();
            }

            if ($invoice->status !== ApInvoiceStatus::PendingApproval) {
                throw new RuntimeException("Only an invoice pending approval can be rejected; [{$invoice->code}] is {$invoice->status->label()}.");
            }

            $invoice->forceFill([
                'status' => ApInvoiceStatus::Rejected,
                'rejected_at' => now(),
                'rejected_by' => $userId,
                'rejection_reason' => $reason,
            ])->save();

            return $invoice;
        });
    }

    /**
     * The terminal human step: an approved invoice becomes one TagERP will
     * actually pay. Deliberately its own action rather than folded into
     * approve(), so "approved" and "activated" stay distinguishable in the
     * audit trail even when the same person does both.
     *
     * @throws RuntimeException
     */
    public function activate(ApInvoice $invoice, ?int $userId = null): ApInvoice
    {
        return DB::transaction(function () use ($invoice, $userId): ApInvoice {
            $invoice->status = $this->lockedStatus($invoice);

            if ($invoice->hasBlockingCycle()) {
                throw new RuntimeException("Invoice [{$invoice->code}] still has an approval cycle in progress and cannot be activated.");
            }

            if ($invoice->status !== ApInvoiceStatus::Approved) {
                throw new RuntimeException("Only an approved invoice can be activated; [{$invoice->code}] is {$invoice->status->label()}.");
            }

            $invoice->forceFill([
                'status' => ApInvoiceStatus::Activated,
                'activated_at' => now(),
                'activated_by' => $userId,
            ])->save();

            return $invoice;
        });
    }

    /**
     * The invoice's own still-running CycleTransaction, if any — null means
     * either no cycle ever matched this invoice, or it did and has already
     * finished (approved/rejected/cancelled), so approve()/reject() fall
     * back to the original single-step behavior in both cases.
     */
    protected function activeCycleTransaction(ApInvoice $invoice): ?CycleTransaction
    {
        return $invoice->cycleTransactions()
            ->where('status', CycleTransactionStatus::InProgress)
            ->latest('id')
            ->first();
    }

    protected function currentPendingLine(CycleTransaction $transaction): ?CycleTransactionLine
    {
        return $transaction->lines()
            ->where('status', CycleTransactionLineStatus::Pending)
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Takes the row lock on this invoice and returns the status as it stands
     * under that lock — the value the caller may act on until the
     * surrounding transaction ends.
     */
    protected function lockedStatus(ApInvoice $invoice): ApInvoiceStatus
    {
        return ApInvoiceStatus::from(
            DB::table('finance_ap_invoices')->where('id', $invoice->getKey())->lockForUpdate()->value('status')
        );
    }

    /**
     * @throws RuntimeException
     */
    protected function assertSubmittable(ApInvoice $invoice): void
    {
        if (! $invoice->status->isEditable()) {
            throw new RuntimeException("Invoice [{$invoice->code}] is already {$invoice->status->label()}.");
        }

        if ($invoice->lines->isEmpty()) {
            throw new RuntimeException("Invoice [{$invoice->code}] needs at least one line.");
        }

        $vendorProfile = $invoice->vendorProfile;

        if ($vendorProfile === null || ! $vendorProfile->is_active) {
            throw new RuntimeException("Invoice [{$invoice->code}] has no active vendor profile to route to.");
        }

        if ($vendorProfile->currency_id !== $invoice->currency_id) {
            throw new RuntimeException(
                "Invoice [{$invoice->code}] is in a different currency than vendor profile [{$vendorProfile->code}]."
            );
        }

        $linesTotal = '0';

        foreach ($invoice->lines as $line) {
            $linesTotal = bcadd($linesTotal, (string) $line->line_total, self::SCALE);
        }

        if (bccomp($linesTotal, (string) $invoice->subtotal, self::SCALE) !== 0) {
            throw new RuntimeException(
                "Invoice [{$invoice->code}] lines total {$linesTotal} but the subtotal is {$invoice->subtotal}."
            );
        }

        $expectedTotal = bcadd((string) $invoice->subtotal, (string) $invoice->tax_amount, self::SCALE);

        if (bccomp($expectedTotal, (string) $invoice->total_amount, self::SCALE) !== 0) {
            throw new RuntimeException(
                "Invoice [{$invoice->code}] subtotal plus tax is {$expectedTotal} but the total is {$invoice->total_amount}."
            );
        }

        $vendor = $vendorProfile->vendor;

        if ($vendor !== null && $vendor->requires_po && blank($invoice->po_reference)) {
            throw new RuntimeException("Vendor [{$vendor->code}] requires a purchase order reference on invoice [{$invoice->code}].");
        }
    }
}
