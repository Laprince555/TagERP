<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Finance\Database\Seeders\GeneralLedger\ApplicationsSeeder as FinanceGeneralLedgerApplicationsSeeder;
use Modules\Finance\Database\Seeders\System\SubModulesSeeder as FinanceSubModulesSeeder;
use Modules\General\Database\Seeders\Security\ApplicationsSeeder as GeneralSecurityApplicationsSeeder;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\System\SystemApplicationsSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\HR\Database\Seeders\EmployeeManagement\ApplicationsSeeder as HREmployeeManagementApplicationsSeeder;
use Modules\HR\Database\Seeders\OrganizationStructure\ApplicationsSeeder as HROrganizationStructureApplicationsSeeder;
use Modules\HR\Database\Seeders\System\SubModulesSeeder as HRSubModulesSeeder;

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
            WorldApplicationsSeeder::class,
            SystemApplicationsSeeder::class,
            GeneralSecurityApplicationsSeeder::class,
            HROrganizationStructureApplicationsSeeder::class,
            HREmployeeManagementApplicationsSeeder::class,
            FinanceGeneralLedgerApplicationsSeeder::class,
            RoleSeeder::class,
            WorldSeeder::class,
        ]);
    }
}
