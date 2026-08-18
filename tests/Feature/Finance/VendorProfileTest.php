<?php

use Modules\Finance\Models\AccountsPayable\VendorFinancialRole;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\Procurement\Models\Vendors\Vendor;

it('generates a hierarchical code scoped to the vendor and financial role', function () {
    $vendor = Vendor::factory()->create();

    $profile = VendorProfile::factory()->create([
        'vendor_id' => $vendor->id,
        'financial_role' => VendorFinancialRole::Supplier,
    ]);

    expect($profile->code)->toStartWith(VendorProfile::APPLICATION_CODE.'-')
        ->and($profile->vendor->is($vendor))->toBeTrue()
        ->and($profile->financial_role)->toBe(VendorFinancialRole::Supplier);
});

it('lets one vendor carry more than one financial profile', function () {
    $vendor = Vendor::factory()->create();

    $materials = VendorProfile::factory()->create(['vendor_id' => $vendor->id, 'financial_role' => VendorFinancialRole::Supplier]);
    $services = VendorProfile::factory()->create(['vendor_id' => $vendor->id, 'financial_role' => VendorFinancialRole::ServiceProvider]);

    expect($vendor->fresh()->code)->not->toBeEmpty()
        ->and($materials->code)->not->toBe($services->code)
        ->and(VendorProfile::where('vendor_id', $vendor->id)->count())->toBe(2);
});
