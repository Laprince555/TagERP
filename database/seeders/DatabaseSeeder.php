<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Finance\Database\Seeders\AccountsPayable\ApplicationsSeeder as FinanceAccountsPayableApplicationsSeeder;
use Modules\Finance\Database\Seeders\AccountsPayable\SubApplicationsSeeder as FinanceAccountsPayableSubApplicationsSeeder;
use Modules\Finance\Database\Seeders\CashAndBanks\SubApplicationsSeeder as FinanceCashAndBanksSubApplicationsSeeder;
use Modules\Finance\Database\Seeders\CashAndBanksSeeder;
use Modules\Finance\Database\Seeders\GeneralLedger\ApplicationsSeeder as FinanceGeneralLedgerApplicationsSeeder;
use Modules\Finance\Database\Seeders\GeneralLedger\SubApplicationsSeeder as FinanceGeneralLedgerSubApplicationsSeeder;
use Modules\Finance\Database\Seeders\System\SubModulesSeeder as FinanceSubModulesSeeder;
use Modules\Finance\Database\Seeders\Tax\ApplicationsSeeder as FinanceTaxApplicationsSeeder;
use Modules\General\Database\Seeders\Security\ApplicationsSeeder as GeneralSecurityApplicationsSeeder;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\System\SystemApplicationsSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\HR\Database\Seeders\Cycles\ApplicationsSeeder as HRCyclesApplicationsSeeder;
use Modules\HR\Database\Seeders\EmployeeManagement\ApplicationsSeeder as HREmployeeManagementApplicationsSeeder;
use Modules\HR\Database\Seeders\OrganizationStructure\ApplicationsSeeder as HROrganizationStructureApplicationsSeeder;
use Modules\HR\Database\Seeders\System\SubModulesSeeder as HRSubModulesSeeder;
use Modules\Inventory\Database\Seeders\StockMovements\ApplicationsSeeder as InventoryStockMovementsApplicationsSeeder;
use Modules\Inventory\Database\Seeders\System\SubModulesSeeder as InventorySubModulesSeeder;
use Modules\Inventory\Database\Seeders\Warehousing\ApplicationsSeeder as InventoryWarehousingApplicationsSeeder;
use Modules\Inventory\Database\Seeders\Warehousing\SubApplicationsSeeder as InventoryWarehousingSubApplicationsSeeder;
use Modules\Procurement\Database\Seeders\System\SubModulesSeeder as ProcurementSubModulesSeeder;
use Modules\Procurement\Database\Seeders\Vendors\ApplicationsSeeder as ProcurementVendorsApplicationsSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ModulesSeeder::class,
            SubModulesSeeder::class,
            HRSubModulesSeeder::class,
            FinanceSubModulesSeeder::class,
            ProcurementSubModulesSeeder::class,
            InventorySubModulesSeeder::class,
            WorldApplicationsSeeder::class,
            SystemApplicationsSeeder::class,
            GeneralSecurityApplicationsSeeder::class,
            HROrganizationStructureApplicationsSeeder::class,
            HREmployeeManagementApplicationsSeeder::class,
            HRCyclesApplicationsSeeder::class,
            FinanceGeneralLedgerApplicationsSeeder::class,
            ProcurementVendorsApplicationsSeeder::class,
            FinanceAccountsPayableApplicationsSeeder::class,
            FinanceTaxApplicationsSeeder::class,
            CashAndBanksSeeder::class,
            InventoryWarehousingApplicationsSeeder::class,
            InventoryStockMovementsApplicationsSeeder::class,
            InventoryWarehousingSubApplicationsSeeder::class,
            FinanceGeneralLedgerSubApplicationsSeeder::class,
            FinanceAccountsPayableSubApplicationsSeeder::class,
            FinanceCashAndBanksSubApplicationsSeeder::class,
            RoleSeeder::class,
            WorldSeeder::class,
        ]);

        Artisan::call('permissions:sync');
    }
}
