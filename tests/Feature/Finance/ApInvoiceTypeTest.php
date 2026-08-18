<?php

use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\ApInvoiceType;

it('generates a hierarchical code for an invoice type', function () {
    $type = ApInvoiceType::factory()->create(['name' => 'Material']);

    expect($type->code)->toStartWith(ApInvoiceType::APPLICATION_CODE.'-')
        ->and($type->slug)->not->toBeEmpty();
});

it('resolves the invoiceType relation when invoice_type_id is set', function () {
    $type = ApInvoiceType::factory()->create(['name' => 'Service']);
    $invoice = ApInvoice::factory()->create(['invoice_type_id' => $type->id]);

    expect($invoice->invoiceType->is($type))->toBeTrue()
        ->and($type->invoices->first()->is($invoice))->toBeTrue();
});

it('stays null-safe when invoice_type_id is null (general invoice)', function () {
    $invoice = ApInvoice::factory()->create(['invoice_type_id' => null]);

    expect($invoice->invoiceType)->toBeNull();
});
