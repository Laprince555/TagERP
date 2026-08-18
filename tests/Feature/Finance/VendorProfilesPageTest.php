<?php

use Modules\Finance\Database\Seeders\AccountsPayable\ApplicationsSeeder as FinanceAccountsPayableApplicationsSeeder;
use Modules\Finance\Database\Seeders\System\SubModulesSeeder as FinanceSubModulesSeeder;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\Procurement\Database\Seeders\System\SubModulesSeeder as ProcurementSubModulesSeeder;
use Modules\Procurement\Database\Seeders\Vendors\ApplicationsSeeder as ProcurementVendorsApplicationsSeeder;

beforeEach(function () {
    $this->seed(ModulesSeeder::class);
    $this->seed(FinanceSubModulesSeeder::class);
    $this->seed(ProcurementSubModulesSeeder::class);
    $this->seed(FinanceAccountsPayableApplicationsSeeder::class);
    $this->seed(ProcurementVendorsApplicationsSeeder::class);
    $this->artisan('permissions:sync');
});

it('renders the vendor profiles index for an authorized user', function () {
    $this->actingAs(superAdmin())
        ->get(route('finance.accounts-payable.vendor-profiles'))
        ->assertOk()
        ->assertSee('Vendor Profiles');
});

it('renders the vendors index for an authorized user', function () {
    $this->actingAs(superAdmin())
        ->get(route('procurement.vendor-management.vendors'))
        ->assertOk()
        ->assertSee('Vendors');
});

it('renders the ap invoices index for an authorized user', function () {
    $this->actingAs(superAdmin())
        ->get(route('finance.accounts-payable.invoices'))
        ->assertOk()
        ->assertSee('Invoices');
});
