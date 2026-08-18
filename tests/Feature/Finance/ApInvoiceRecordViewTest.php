<?php

use Modules\Finance\Database\Seeders\AccountsPayable\ApplicationsSeeder as FinanceAccountsPayableApplicationsSeeder;
use Modules\Finance\Database\Seeders\System\SubModulesSeeder as FinanceSubModulesSeeder;
use Modules\Finance\Models\AccountsPayable\ApInvoice;
use Modules\Finance\Models\AccountsPayable\ApInvoiceLine;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\General\Database\Seeders\System\ModulesSeeder;
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
});

it('renders an invoice record view with its lines tab for an authorized user', function () {
    $vendor = Vendor::factory()->create();
    $profile = VendorProfile::factory()->create(['vendor_id' => $vendor->id]);
    $invoice = ApInvoice::factory()->create(['vendor_profile_id' => $profile->id, 'currency_id' => $profile->currency_id]);
    ApInvoiceLine::factory()->create(['invoice_id' => $invoice->id]);

    $this->actingAs(superAdmin())
        ->get(route('finance.accounts-payable.invoices.show', ['recordId' => $invoice->id]))
        ->assertOk()
        ->assertSee($invoice->invoice_number);
});
