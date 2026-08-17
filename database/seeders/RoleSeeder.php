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

        // A known email with a known password is a convenience locally and a
        // way in everywhere else. The role itself is still created in every
        // environment; only the account that carries it is held back.
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('Skipping the default admin account outside local/testing. Create one with a real password.');

            return;
        }

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
