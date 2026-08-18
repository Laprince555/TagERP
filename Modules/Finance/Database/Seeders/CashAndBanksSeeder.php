<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Database\Seeders\CashAndBanks\ApplicationsSeeder;
use Modules\Finance\Database\Seeders\CashAndBanks\SubApplicationsSeeder;

/**
 * The "Cash & Banks" sub-module itself is seeded by the master
 * Finance\Database\Seeders\System\SubModulesSeeder (code fin-csh); this
 * only seeds the Applications hung under it.
 */
class CashAndBanksSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ApplicationsSeeder::class);
        $this->call(SubApplicationsSeeder::class);
    }
}
