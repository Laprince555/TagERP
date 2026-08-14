<?php

namespace Modules\General\Database\Seeders\System;

use App\Services\NavigationTreeService;
use Illuminate\Database\Seeder;
use Modules\General\Database\Seeders\System\Concerns\EncodesTranslatableAttributes;
use Modules\General\System\Module;
use Modules\General\System\SubModule;

class ModulesSeeder extends Seeder
{
    use EncodesTranslatableAttributes;

    /**
     * Modules that were seeded under a superseded identity, mapped to their canonical code.
     *
     * @var array<string, string>
     */
    private const LEGACY_CODE_CORRECTIONS = [
        'sal' => 'crm',
        'ven' => 'proc',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->correctLegacyModules();

        $modules = [
            [
                'name' => ['ar' => 'المالية', 'en' => 'Finance'],
                'description' => ['ar' => 'إدارة العمليات المالية والحسابات', 'en' => 'Manage financial operations and accounting'],
                'code' => 'fin',
                'route' => 'finance',
                'icon' => 'banknotes',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الموارد البشرية', 'en' => 'HR'],
                'description' => ['ar' => 'إدارة الموظفين والهيكل الوظيفي', 'en' => 'Manage employees and organizational structure'],
                'code' => 'hr',
                'route' => 'hr',
                'icon' => 'users',
                'sort_order' => 1,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'إدارة العلاقات والعملاء', 'en' => 'CRM'],
                'description' => ['ar' => 'إدارة العملاء والمبيعات والتفاعلات التجارية', 'en' => 'Manage customers, sales pipelines, and commercial interactions'],
                'code' => 'crm',
                'route' => 'crm',
                'icon' => 'shopping-cart',
                'sort_order' => 2,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'المشتريات والتوريد', 'en' => 'Procurement'],
                'description' => ['ar' => 'إدارة الموردين والمشتريات ودورات التوريد', 'en' => 'Manage vendors, purchasing, and procurement workflows'],
                'code' => 'proc',
                'route' => 'procurement',
                'icon' => 'truck',
                'sort_order' => 3,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'المخزون', 'en' => 'Inventory'],
                'description' => ['ar' => 'إدارة المخزون والمستودعات وحركات الأصناف وتوافرها.', 'en' => 'Manage inventory, warehouses, stock movements, and item availability.'],
                'code' => 'inv',
                'route' => 'inventory',
                'icon' => 'archive-box',
                'sort_order' => 4,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'المشاريع', 'en' => 'Projects'],
                'description' => ['ar' => 'إدارة المشاريع والمهام والمتابعة', 'en' => 'Manage projects, tasks, and follow-up'],
                'code' => 'prj',
                'route' => 'projects',
                'icon' => 'briefcase',
                'sort_order' => 5,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'عام', 'en' => 'General'],
                'description' => ['ar' => 'الإعدادات العامة ووظائف النظام الأساسية', 'en' => 'General settings and core system functions'],
                'code' => 'gen',
                'route' => 'general',
                'icon' => 'cog-6-tooth',
                'sort_order' => 6,
                'permission_group' => null,
                'is_active' => true,
            ],
        ];

        $preparedModules = array_map(function (array $module): array {
            return $this->encodeTranslatableAttributes($module);
        }, $modules);

        Module::upsert(
            $preparedModules,
            ['code'],
            ['name', 'description', 'route', 'icon', 'sort_order', 'permission_group', 'is_active']
        );

        // `upsert` bypasses model events, so the navigation observer never fires for it.
        app(NavigationTreeService::class)->invalidateCache();
    }

    /**
     * Re-key superseded module rows onto their canonical code, in place.
     *
     * The row keeps its id so existing sub modules, applications and permissions stay
     * attached; the upsert below then applies the canonical name, description, icon,
     * route and sort order to that same record. Runs before the upsert so the canonical
     * code is never inserted as a second row beside the legacy one.
     */
    private function correctLegacyModules(): void
    {
        foreach (self::LEGACY_CODE_CORRECTIONS as $legacyCode => $canonicalCode) {
            $legacyModule = Module::where('code', $legacyCode)->first();

            if ($legacyModule === null) {
                continue;
            }

            $canonicalModule = Module::where('code', $canonicalCode)->first();

            if ($canonicalModule === null) {
                $legacyModule->forceFill(['code' => $canonicalCode])->save();

                continue;
            }

            // A canonical row already exists beside the legacy one, so the legacy children
            // are moved across before the superseded row is dropped. Deleting first would
            // hit the restrictive foreign key on `sub_modules`.
            SubModule::where('module_id', $legacyModule->id)
                ->update(['module_id' => $canonicalModule->id]);

            $legacyModule->delete();
        }
    }
}
