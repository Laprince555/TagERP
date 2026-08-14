<?php

namespace Modules\General\Database\Seeders\System;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\General\Database\Seeders\System\Concerns\EncodesTranslatableAttributes;
use Modules\General\System\Module;
use Modules\General\System\SubModule;
use RuntimeException;

class SubModulesSeeder extends Seeder
{
    use EncodesTranslatableAttributes;
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $generalModule = Module::where('code', 'gen')->first();

        if (! $generalModule) {
            throw new RuntimeException('General module with code "gen" was not found.');
        }

        $generalSubModules = [
            [
                'name' => ['ar' => 'إعدادات النظام', 'en' => 'System Settings'],
                'description' => ['ar' => 'إعدادات النظام العامة وخيارات التهيئة الأساسية.', 'en' => 'General system settings and core configuration options.'],
                'code' => 'gen-sys',
                'route' => 'general.system',
                'icon' => 'cpu-chip',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'العالم والجغرافيا', 'en' => 'World & Geography'],
                'description' => ['ar' => 'إدارة الدول والمناطق والمدن والبيانات الجغرافية المرجعية.', 'en' => 'Manage countries, regions, cities, and reference geography data.'],
                'code' => 'gen-wld',
                'route' => 'general.world',
                'icon' => 'globe-alt',
                'sort_order' => 1,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الأمان والقواعد', 'en' => 'Security & Rules'],
                'description' => ['ar' => 'التحكم في السياسات والصلاحيات وقواعد الحماية العامة.', 'en' => 'Control policies, permissions, and general security rules.'],
                'code' => 'gen-sec',
                'route' => 'general.security',
                'icon' => 'shield-check',
                'sort_order' => 2,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'التدقيق والسجلات', 'en' => 'Audit & Logs'],
                'description' => ['ar' => 'متابعة سجلات النشاط والتدقيق وتتبع التغييرات.', 'en' => 'Track activity logs, audits, and change history.'],
                'code' => 'gen-aud',
                'route' => 'general.audit',
                'icon' => 'clipboard-document-list',
                'sort_order' => 3,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الإشعارات', 'en' => 'Notifications'],
                'description' => ['ar' => 'إدارة قنوات الإشعار والقوالب والتنبيهات العامة.', 'en' => 'Manage notification channels, templates, and general alerts.'],
                'code' => 'gen-ntf',
                'route' => 'general.notifications',
                'icon' => 'bell',
                'sort_order' => 4,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الملفات والوسائط', 'en' => 'Files & Media'],
                'description' => ['ar' => 'إدارة الملفات والمرفقات والوسائط المشتركة في النظام.', 'en' => 'Manage files, attachments, and shared media across the system.'],
                'code' => 'gen-fil',
                'route' => 'general.files',
                'icon' => 'folder',
                'sort_order' => 5,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'التطبيقات العامة', 'en' => 'General Applications'],
                'description' => ['ar' => 'نقطة تجميع للتطبيقات والخدمات العامة التابعة لوحدة General.', 'en' => 'A grouping point for applications and shared services under the General module.'],
                'code' => 'gen-app',
                'route' => 'general.applications',
                'icon' => 'squares-2x2',
                'sort_order' => 6,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'التوثيق والدليل', 'en' => 'Documentation'],
                'description' => ['ar' => 'التوثيق التقني لنظام TagERP وتدريب المطورين.', 'en' => 'TagERP system technical documentation and guides.'],
                'code' => 'gen-doc',
                'route' => 'general.docs',
                'icon' => 'book-open',
                'sort_order' => 7,
                'permission_group' => null,
                'is_active' => true,
            ],
        ];

        $subModules = array_map(function (array $subModule) use ($generalModule): array {
            $subModule = $this->encodeTranslatableAttributes($subModule);
            $subModule['module_id'] = $generalModule->id;

            return $subModule;
        }, $generalSubModules);

        SubModule::query()->upsert(
            $subModules,
            ['code'],
            ['name', 'description', 'route', 'icon', 'sort_order', 'permission_group', 'is_active', 'module_id'],
        );
    }
}
