<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@tagerp.com'],
            [
                'name' => 'admin',
                'password' => 'password',
            ],
        );

        $adminUser->assignRole($superAdminRole);
    }
}
