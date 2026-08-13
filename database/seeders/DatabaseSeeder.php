<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            \Modules\General\Database\Seeders\System\ModulesSeeder::class,
            \Modules\General\Database\Seeders\System\SubModulesSeeder::class,
            RoleSeeder::class,
            WorldSeeder::class,
        ]);
    }
}
