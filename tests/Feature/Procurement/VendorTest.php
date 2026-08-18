<?php

use Modules\General\Models\World\Companies\Company;
use Modules\Procurement\Models\Vendors\Vendor;
use Modules\Procurement\Models\Vendors\VendorType;

it('generates a hierarchical code from the application code and a unique slug', function () {
    $company = Company::factory()->create(['name' => 'Acme Supplies']);

    $vendor = Vendor::factory()->create([
        'company_id' => $company->id,
        'vendor_type' => VendorType::Supplier,
    ]);

    expect($vendor->code)->toStartWith(Vendor::APPLICATION_CODE.'-')
        ->and($vendor->slug)->not->toBeEmpty()
        ->and($vendor->vendor_type)->toBe(VendorType::Supplier);
});

it('deduplicates the slug when two vendors resolve to the same base', function () {
    $companyA = Company::factory()->create(['name' => 'Northwind Ltd']);
    $companyB = Company::factory()->create(['name' => 'Northwind Ltd']);

    $vendorA = Vendor::factory()->create(['company_id' => $companyA->id]);
    $vendorB = Vendor::factory()->create(['company_id' => $companyB->id]);

    expect($vendorA->slug)->not->toBe($vendorB->slug)
        ->and($vendorA->code)->not->toBe($vendorB->code);
});
