<?php

use App\Models\User;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\ApInvoiceLine;
use Modules\Finance\Models\AccountsPayable\ApInvoiceStatus;
use Modules\Finance\Models\AccountsPayable\ApInvoiceType;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\Finance\Services\AccountsPayable\ApInvoiceApprover;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleLine;
use Modules\HR\Models\EmployeeManagement\Employee;
use Modules\Procurement\Models\Vendors\Vendor;

beforeEach(function () {
    $this->clerk = User::factory()->create()->id;
    $this->approver = User::factory()->create()->id;
});

function makeBalancedInvoice(array $vendorAttributes = [], array $invoiceAttributes = []): ApInvoice
{
    $currency = Currency::factory()->create();
    $vendor = Vendor::factory()->create(array_merge(['requires_po' => false], $vendorAttributes));
    $profile = VendorProfile::factory()->create(['vendor_id' => $vendor->id, 'currency_id' => $currency->id]);

    $invoice = ApInvoice::factory()->create(array_merge([
        'vendor_profile_id' => $profile->id,
        'currency_id' => $currency->id,
        'subtotal' => 200,
        'tax_amount' => 20,
        'total_amount' => 220,
    ], $invoiceAttributes));

    ApInvoiceLine::factory()->create(['invoice_id' => $invoice->id, 'quantity' => 2, 'unit_price' => 100]);

    return $invoice->fresh();
}

it('carries a balanced draft invoice through submit, approve, and activate', function () {
    $invoice = makeBalancedInvoice();
    $approver = app(ApInvoiceApprover::class);

    $approver->submit($invoice, $this->clerk);
    expect($invoice->fresh()->status)->toBe(ApInvoiceStatus::PendingApproval);

    $approver->approve($invoice, $this->approver);
    expect($invoice->fresh()->status)->toBe(ApInvoiceStatus::Approved);

    $approver->activate($invoice, $this->approver);
    expect($invoice->fresh()->status)->toBe(ApInvoiceStatus::Activated);
});

it('lets a rejected invoice be resubmitted', function () {
    $invoice = makeBalancedInvoice();
    $approver = app(ApInvoiceApprover::class);

    $approver->submit($invoice, $this->clerk);
    $approver->reject($invoice, $this->approver, 'wrong PO');
    expect($invoice->fresh()->status)->toBe(ApInvoiceStatus::Rejected);

    $approver->submit($invoice->fresh(), $this->clerk);
    expect($invoice->fresh()->status)->toBe(ApInvoiceStatus::PendingApproval);
});

it('refuses to submit when the lines do not sum to the subtotal', function () {
    $invoice = makeBalancedInvoice(invoiceAttributes: ['subtotal' => 999, 'total_amount' => 1019]);

    app(ApInvoiceApprover::class)->submit($invoice, $this->clerk);
})->throws(RuntimeException::class, 'lines total');

it('refuses to submit when subtotal plus tax does not equal the total', function () {
    $invoice = makeBalancedInvoice(invoiceAttributes: ['total_amount' => 999]);

    app(ApInvoiceApprover::class)->submit($invoice, $this->clerk);
})->throws(RuntimeException::class, 'subtotal plus tax');

it('refuses to submit when the vendor requires a PO and none is given', function () {
    $invoice = makeBalancedInvoice(vendorAttributes: ['requires_po' => true], invoiceAttributes: ['po_reference' => null]);

    app(ApInvoiceApprover::class)->submit($invoice, $this->clerk);
})->throws(RuntimeException::class, 'requires a purchase order');

it('refuses to submit when the invoice currency does not match the vendor profile currency', function () {
    $invoice = makeBalancedInvoice();
    $invoice->forceFill(['currency_id' => Currency::factory()->create()->id])->save();

    app(ApInvoiceApprover::class)->submit($invoice, $this->clerk);
})->throws(RuntimeException::class, 'different currency');

it('refuses to approve or activate out of order', function () {
    $invoice = makeBalancedInvoice();
    $approver = app(ApInvoiceApprover::class);

    expect(fn () => $approver->approve($invoice, $this->clerk))->toThrow(RuntimeException::class, 'pending approval');
    expect(fn () => $approver->activate($invoice, $this->clerk))->toThrow(RuntimeException::class, 'approved invoice');
});

it('blocks editing an invoice once it has left draft', function () {
    $invoice = makeBalancedInvoice();
    app(ApInvoiceApprover::class)->submit($invoice, $this->clerk);

    expect(fn () => $invoice->fresh()->update(['invoice_number' => 'changed']))
        ->toThrow(RuntimeException::class, 'cannot be modified');
});

/**
 * An invoice whose type resolves to a real HR Cycle: submit() must start a
 * CycleTransaction (not jump straight to a single-step approval), each
 * approve() call must route through that cycle stage-by-stage in job_title
 * order, and the invoice must only reach Approved once every stage has, with
 * activate() blocked for as long as the cycle is still open.
 */
it('routes a typed invoice through its matching HR cycle stage by stage to Approved', function () {
    $invoiceType = ApInvoiceType::factory()->create();

    $cycle = Cycle::factory()->create([
        'subject_model' => ApInvoice::class,
        'document_type_value' => $invoiceType->slug,
    ]);

    $firstApproverUser = User::factory()->create();
    $firstApprover = Employee::factory()->create(['user_id' => $firstApproverUser->id]);

    $secondApproverUser = User::factory()->create();
    $secondApprover = Employee::factory()->create(['user_id' => $secondApproverUser->id]);

    CycleLine::factory()->for($cycle)->create([
        'sequence' => 1,
        'job_title_id' => $firstApprover->job_title_id,
        'job_grade_id' => null,
        'target_status_on_approve' => null,
    ]);

    CycleLine::factory()->for($cycle)->create([
        'sequence' => 2,
        'job_title_id' => $secondApprover->job_title_id,
        'job_grade_id' => null,
        'target_status_on_approve' => 'approved',
    ]);

    $invoice = makeBalancedInvoice(invoiceAttributes: ['invoice_type_id' => $invoiceType->id]);
    $approver = app(ApInvoiceApprover::class);

    $approver->submit($invoice, $this->clerk);
    expect($invoice->fresh()->status)->toBe(ApInvoiceStatus::PendingApproval)
        ->and($invoice->fresh()->hasBlockingCycle())->toBeTrue();

    // Someone unrelated to either stage cannot short-circuit the cycle.
    expect(fn () => $approver->approve($invoice->fresh(), $this->clerk))
        ->toThrow(RuntimeException::class, 'not authorized');

    $approver->approve($invoice->fresh(), $firstApproverUser->id);
    expect($invoice->fresh()->status)->toBe(ApInvoiceStatus::PendingApproval)
        ->and($invoice->fresh()->hasBlockingCycle())->toBeTrue();

    // activate() refuses to run while the cycle transaction is still open,
    // independent of the header's own status field.
    $invoice->fresh()->forceFill(['status' => ApInvoiceStatus::Approved])->save();
    expect(fn () => $approver->activate($invoice->fresh(), $secondApproverUser->id))
        ->toThrow(RuntimeException::class, 'approval cycle in progress');
    $invoice->fresh()->forceFill(['status' => ApInvoiceStatus::PendingApproval])->save();

    $approver->approve($invoice->fresh(), $secondApproverUser->id);
    expect($invoice->fresh()->status)->toBe(ApInvoiceStatus::Approved)
        ->and($invoice->fresh()->hasBlockingCycle())->toBeFalse();

    $approver->activate($invoice->fresh(), $secondApproverUser->id);
    expect($invoice->fresh()->status)->toBe(ApInvoiceStatus::Activated);
});

it('rejects a typed invoice through its cycle and leaves it Rejected', function () {
    $invoiceType = ApInvoiceType::factory()->create();

    $cycle = Cycle::factory()->create([
        'subject_model' => ApInvoice::class,
        'document_type_value' => $invoiceType->slug,
    ]);

    $approverUser = User::factory()->create();
    $approverEmployee = Employee::factory()->create(['user_id' => $approverUser->id]);

    CycleLine::factory()->for($cycle)->create([
        'sequence' => 1,
        'job_title_id' => $approverEmployee->job_title_id,
        'job_grade_id' => null,
        'target_status_on_reject' => 'rejected',
    ]);

    $invoice = makeBalancedInvoice(invoiceAttributes: ['invoice_type_id' => $invoiceType->id]);
    $approver = app(ApInvoiceApprover::class);

    $approver->submit($invoice, $this->clerk);
    $approver->reject($invoice->fresh(), $approverUser->id, 'missing backup');

    // hasBlockingCycle() only excludes Approved/Cancelled transactions, so a
    // Rejected one still counts as "blocking" — by design: activate() must
    // never run against it, and a rejected invoice can only move forward by
    // being resubmitted, which starts a fresh CycleTransaction anyway.
    expect($invoice->fresh()->status)->toBe(ApInvoiceStatus::Rejected)
        ->and($invoice->fresh()->hasBlockingCycle())->toBeTrue();
});
