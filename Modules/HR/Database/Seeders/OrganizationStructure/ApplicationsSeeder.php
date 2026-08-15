<?php

namespace Modules\HR\Database\Seeders\OrganizationStructure;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\General\System\Application;
use Modules\General\System\SubModule;
use RuntimeException;

class ApplicationsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orgSubModule = SubModule::where('code', 'hr-org')->first();

        if (! $orgSubModule) {
            throw new RuntimeException('Organization Structure submodule with code "hr-org" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'الكيانات', 'en' => 'Entities'],
                'description' => ['ar' => 'إدارة الكيانات الإدارية وربطها بالشركات المسجلة.', 'en' => 'Manage the entities you administer and their link to registered companies.'],
                'code' => 'hr-org-ent',
                'route' => 'hr.organization-structure.entities',
                'icon' => 'building-office-2',
                'color' => 'cyan',
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الفروع', 'en' => 'Branches'],
                'description' => ['ar' => 'إدارة فروع كل كيان وتحديد الفرع الرئيسي.', 'en' => 'Manage each entity\'s branches and which one is the main branch.'],
                'code' => 'hr-org-brn',
                'route' => 'hr.organization-structure.branches',
                'icon' => 'map-pin',
                'color' => 'indigo',
                'sort_order' => 1,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الإدارات', 'en' => 'Departments'],
                'description' => ['ar' => 'إدارة كتالوج الإدارات العام وربطها بالكيانات.', 'en' => 'Manage the group-wide department catalog and which entities have each active.'],
                'code' => 'hr-org-dep',
                'route' => 'hr.organization-structure.departments',
                'icon' => 'rectangle-group',
                'color' => 'violet',
                'sort_order' => 2,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الدرجات الوظيفية', 'en' => 'Job Grades'],
                'description' => ['ar' => 'إدارة سلم الدرجات الوظيفية العام للمجموعة.', 'en' => 'Manage the group-wide job grade ladder.'],
                'code' => 'hr-org-jbg',
                'route' => 'hr.organization-structure.job-grades',
                'icon' => 'chart-bar',
                'color' => 'amber',
                'sort_order' => 3,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الوظائف', 'en' => 'Job Titles'],
                'description' => ['ar' => 'إدارة الألقاب الوظيفية وربطها بالإدارات والدرجات.', 'en' => 'Manage job titles and their link to departments and grades.'],
                'code' => 'hr-org-jbt',
                'route' => 'hr.organization-structure.job-titles',
                'icon' => 'identification',
                'color' => 'emerald',
                'sort_order' => 4,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($orgSubModule): array {
            foreach (['name', 'description'] as $attribute) {
                $application[$attribute] = json_encode($application[$attribute], JSON_UNESCAPED_UNICODE);
            }

            $application['submodule_id'] = $orgSubModule->id;

            return $application;
        }, $applications);

        Application::query()->upsert(
            $prepared,
            ['code'],
            ['name', 'description', 'route', 'icon', 'color', 'sort_order', 'permission_name', 'permission_group', 'custom_actions', 'is_active', 'submodule_id'],
        );
    }
}
