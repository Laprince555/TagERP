<?php

use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxCategory;
use Modules\General\Models\World\Country;

it('generates a hierarchical code for a tax category', function () {
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);

    $category = TaxCategory::factory()->create(['name' => 'Value Added Tax', 'country_id' => $country->id]);

    expect($category->code)->toStartWith(TaxCategory::APPLICATION_CODE.'-')
        ->and($category->slug)->not->toBeEmpty()
        ->and($category->country->is($country))->toBeTrue();
});

it('generates a hierarchical code for a deduction category', function () {
    $category = DeductionCategory::factory()->create(['name' => 'Late Delivery Penalty']);

    expect($category->code)->toStartWith(DeductionCategory::APPLICATION_CODE.'-')
        ->and($category->slug)->not->toBeEmpty();
});

it('generates a hierarchical code for a tax and links it to its category', function () {
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);
    $category = TaxCategory::factory()->create(['name' => 'Value Added Tax', 'country_id' => $country->id]);

    $tax = Tax::factory()->create(['name' => 'Standard VAT', 'tax_category_id' => $category->id, 'direction' => 'addition', 'rate' => 14]);

    expect($tax->code)->toStartWith(Tax::APPLICATION_CODE.'-')
        ->and($tax->category->is($category))->toBeTrue()
        ->and((float) $tax->rate)->toBe(14.0);
});

it('generates a hierarchical code for a deduction and links it to its category', function () {
    $category = DeductionCategory::factory()->create(['name' => 'Penalties']);

    $deduction = Deduction::factory()->create(['name' => 'Late Delivery Penalty', 'deduction_category_id' => $category->id, 'calculation_type' => 'percentage', 'value' => 5]);

    expect($deduction->code)->toStartWith(Deduction::APPLICATION_CODE.'-')
        ->and($deduction->category->is($category))->toBeTrue()
        ->and((float) $deduction->value)->toBe(5.0);
});
