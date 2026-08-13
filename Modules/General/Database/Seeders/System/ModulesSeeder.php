<?php

namespace Modules\General\Database\Seeders\System;

use Illuminate\Database\Seeder;
use Modules\General\System\Module;

class ModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'المبيعات', 'en' => 'Sales'],
                'description' => ['ar' => 'إدارة المبيعات والعملاء والفواتير', 'en' => 'Manage sales, customers, and invoices'],
                'code' => 'sal',
                'route' => 'sales',
                'icon' => 'shopping-cart',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الموردون', 'en' => 'Vendors'],
                'description' => ['ar' => 'إدارة بيانات الموردين والتعاملات', 'en' => 'Manage vendor records and transactions'],
                'code' => 'ven',
                'route' => 'vendors',
                'icon' => 'truck',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'المخزون', 'en' => 'Inventory'],
                'description' => ['ar' => 'إدارة الأصناف والمخازن وحركة المخزون', 'en' => 'Manage items, warehouses, and stock movement'],
                'code' => 'inv',
                'route' => 'inventory',
                'icon' => 'archive-box',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'المشاريع', 'en' => 'Projects'],
                'description' => ['ar' => 'إدارة المشاريع والمهام والمتابعة', 'en' => 'Manage projects, tasks, and follow-up'],
                'code' => 'prj',
                'route' => 'projects',
                'icon' => 'briefcase',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'عام', 'en' => 'General'],
                'description' => ['ar' => 'الإعدادات العامة ووظائف النظام الأساسية', 'en' => 'General settings and core system functions'],
                'code' => 'gen',
                'route' => 'general',
                'icon' => 'cog-6-tooth',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
        ];

        $preparedModules = array_map(function (array $module): array {
            $module['name'] = json_encode($module['name'], JSON_UNESCAPED_UNICODE);
            $module['description'] = json_encode($module['description'], JSON_UNESCAPED_UNICODE);

            return $module;
        }, $modules);

        Module::upsert(
            $preparedModules,
            ['code'],
            ['name', 'description', 'route', 'icon', 'sort_order', 'permission_group', 'is_active']
        );
    }
}
