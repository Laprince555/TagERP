<?php

use Livewire\Livewire;
use Modules\Finance\Database\Seeders\AccountsPayable\ApplicationsSeeder as FinanceAccountsPayableApplicationsSeeder;
use Modules\Finance\Database\Seeders\System\SubModulesSeeder as FinanceSubModulesSeeder;
use Modules\Finance\Livewire\AccountsPayable\Invoices\ApInvoiceDeductionLineEditor;
use Modules\Finance\Livewire\AccountsPayable\Invoices\ApInvoiceTaxLineEditor;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\Finance\Models\Tax\Tax;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Models\World\Country;
use Modules\General\Models\World\Currency;
use Modules\Procurement\Database\Seeders\System\SubModulesSeeder as ProcurementSubModulesSeeder;
use Modules\Procurement\Database\Seeders\Vendors\ApplicationsSeeder as ProcurementVendorsApplicationsSeeder;
use Modules\Procurement\Models\Vendors\Vendor;

beforeEach(function () {
    $this->seed(ModulesSeeder::class);
    $this->seed(FinanceSubModulesSeeder::class);
    $this->seed(ProcurementSubModulesSeeder::class);
    $this->seed(FinanceAccountsPayableApplicationsSeeder::class);
    $this->seed(ProcurementVendorsApplicationsSeeder::class);
    $this->artisan('permissions:sync');

    $this->actingAs(superAdmin());

    Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);

    $currency = Currency::factory()->create();
    $vendor = Vendor::factory()->create(['requires_po' => false]);
    $profile = VendorProfile::factory()->create(['vendor_id' => $vendor->id, 'currency_id' => $currency->id]);

    $this->invoice = ApInvoice::factory()->create([
        'vendor_profile_id' => $profile->id,
        'currency_id' => $currency->id,
    ]);
});

it('adds a tax line to a draft invoice through the editor', function () {
    $tax = Tax::factory()->create();

    Livewire::test(ApInvoiceTaxLineEditor::class, ['recordId' => $this->invoice->id])
        ->set('rows.0.tax_id', $tax->id)
        ->set('rows.0.taxable_amount', '1000')
        ->set('rows.0.tax_amount', '140')
        ->call('save')
        ->assertRedirect(route('finance.accounts-payable.invoices.show', ['recordId' => $this->invoice->id]));

    expect($this->invoice->fresh()->taxLines)->toHaveCount(1)
        ->and($this->invoice->fresh()->taxLines->first()->tax_id)->toBe($tax->id);
});

it('adds a deduction line to a draft invoice through the editor', function () {
    $deduction = Deduction::factory()->create();

    Livewire::test(ApInvoiceDeductionLineEditor::class, ['recordId' => $this->invoice->id])
        ->set('rows.0.deduction_id', $deduction->id)
        ->set('rows.0.amount', '50')
        ->call('save')
        ->assertRedirect(route('finance.accounts-payable.invoices.show', ['recordId' => $this->invoice->id]));

    expect($this->invoice->fresh()->deductionLines)->toHaveCount(1)
        ->and($this->invoice->fresh()->deductionLines->first()->deduction_id)->toBe($deduction->id);
});
