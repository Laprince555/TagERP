<?php

namespace Modules\General\Database\Seeders\System;

use App\Services\NavigationTreeService;
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
                // `assign` is what gates attaching/detaching roles on a user —
                // deliberately separate from `update`, because granting a role
                // is granting everything that role can do, and an operator who
                // may fix a name is not automatically an operator who may hand
                // out super_admin.
                'custom_actions' => json_encode(['impersonate', 'reset-password', 'block', 'assign']),
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'النسخ الاحتياطية', 'en' => 'Backups'],
                'description' => ['ar' => 'إدارة النسخ الاحتياطية للنظام وقاعدة البيانات والملفات.', 'en' => 'Manage system backups, database backups, and file backups.'],
                'code' => 'gen-sys-bkp',
                'route' => 'general.system.backups',
                'icon' => 'archive-box',
                'color' => 'blue',
                'sort_order' => 1,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => json_encode(['create', 'download', 'delete']),
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
            ['name', 'description', 'route', 'icon', 'color', 'sort_order', 'permission_group', 'custom_actions', 'is_active', 'submodule_id'],
        );

        // `upsert` bypasses model events, so the navigation observer never fires for it.
        app(NavigationTreeService::class)->invalidateCache();
    }
}
