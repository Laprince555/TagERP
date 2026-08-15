<?php

namespace Modules\HR\Database\Seeders\System;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\General\System\Module;
use Modules\General\System\SubModule;
use RuntimeException;

class SubModulesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hrModule = Module::where('code', 'hr')->first();

        if (! $hrModule) {
            throw new RuntimeException('HR module with code "hr" was not found.');
        }

        $hrSubModules = [
            [
                'name' => ['ar' => 'الهيكل التنظيمي', 'en' => 'Organization Structure'],
                'description' => ['ar' => 'إدارة الكيانات والفروع والإدارات والوظائف والدرجات الوظيفية.', 'en' => 'Manage entities, branches, departments, job titles, and job grades.'],
                'code' => 'hr-org',
                'route' => 'hr.organization-structure',
                'icon' => 'building-office-2',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'إدارة الموظفين', 'en' => 'Employee Management'],
                'description' => ['ar' => 'تعريف الموظفين وربطهم بالهيكل التنظيمي والوظائف.', 'en' => 'Define employees and link them to the organization structure and job assignments.'],
                'code' => 'hr-emp',
                'route' => 'hr.employee-management',
                'icon' => 'identification',
                'sort_order' => 1,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'تتبع الوقت', 'en' => 'Time Tracking'],
                'description' => ['ar' => 'الحضور والانصراف والإجازات وجداول العمل.', 'en' => 'Attendance, leave, and work schedules.'],
                'code' => 'hr-tim',
                'route' => 'hr.time-tracking',
                'icon' => 'clock',
                'sort_order' => 2,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'تقييم الأداء', 'en' => 'Performance Tracking'],
                'description' => ['ar' => 'دورات التقييم والأهداف ومتابعة الأداء الوظيفي.', 'en' => 'Review cycles, goals, and performance tracking.'],
                'code' => 'hr-prf',
                'route' => 'hr.performance-tracking',
                'icon' => 'chart-bar',
                'sort_order' => 3,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'إدارة الرواتب', 'en' => 'Payroll Management'],
                'description' => ['ar' => 'مسير الرواتب والبدلات والاستقطاعات.', 'en' => 'Payroll runs, allowances, and deductions.'],
                'code' => 'hr-pay',
                'route' => 'hr.payroll-management',
                'icon' => 'banknotes',
                'sort_order' => 4,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'التوظيف واستقطاب الكفاءات', 'en' => 'Recruitment & Talent Acquisition'],
                'description' => ['ar' => 'الوظائف الشاغرة والمتقدمين ودورة التوظيف.', 'en' => 'Job openings, candidates, and the hiring pipeline.'],
                'code' => 'hr-rec',
                'route' => 'hr.recruitment',
                'icon' => 'user-plus',
                'sort_order' => 5,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الخدمة الذاتية للموظف', 'en' => 'Employee Self Service'],
                'description' => ['ar' => 'وصول الموظف المباشر لبياناته وطلباته الخاصة.', 'en' => 'Direct employee access to their own data and requests.'],
                'code' => 'hr-ess',
                'route' => 'hr.employee-self-service',
                'icon' => 'user-circle',
                'sort_order' => 6,
                'permission_group' => null,
                'is_active' => true,
            ],
        ];

        $subModules = array_map(function (array $subModule) use ($hrModule): array {
            $subModule = $this->encodeTranslatableAttributes($subModule);
            $subModule['module_id'] = $hrModule->id;

            return $subModule;
        }, $hrSubModules);

        SubModule::query()->upsert(
            $subModules,
            ['code'],
            ['name', 'description', 'route', 'icon', 'sort_order', 'permission_group', 'is_active', 'module_id'],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function encodeTranslatableAttributes(array $payload): array
    {
        foreach (['name', 'description'] as $attribute) {
            if (isset($payload[$attribute]) && is_array($payload[$attribute])) {
                $payload[$attribute] = json_encode($payload[$attribute], JSON_UNESCAPED_UNICODE);
            }
        }

        return $payload;
    }
}
