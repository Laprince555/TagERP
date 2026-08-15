<?php

namespace Modules\General\Database\Seeders\System;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\General\Database\Seeders\System\Concerns\EncodesTranslatableAttributes;
use Modules\General\System\Application;
use Modules\General\System\SubModule;
use RuntimeException;

class SystemApplicationsSeeder extends Seeder
{
    use EncodesTranslatableAttributes;
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $systemSubModule = SubModule::where('code', 'gen-sys')->first();

        if (! $systemSubModule) {
            throw new RuntimeException('System submodule with code "gen-sys" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'المستخدمون', 'en' => 'Users'],
                'description' => ['ar' => 'إدارة حسابات دخول النظام، وربطها اختياريًا بملف موظف.', 'en' => 'Manage system login accounts, optionally linked to an employee record.'],
                'code' => 'gen-sys-usr',
                'route' => 'general.system.users',
                'icon' => 'user',
                'color' => 'slate',
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($systemSubModule): array {
            $application = $this->encodeTranslatableAttributes($application);
            $application['submodule_id'] = $systemSubModule->id;

            return $application;
        }, $applications);

        Application::query()->upsert(
            $prepared,
            ['code'],
            ['name', 'description', 'route', 'icon', 'color', 'sort_order', 'permission_name', 'permission_group', 'is_active', 'submodule_id'],
        );
    }
}
