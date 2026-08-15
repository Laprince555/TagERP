<?php

namespace Modules\HR\Database\Seeders\EmployeeManagement;

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
        $empSubModule = SubModule::where('code', 'hr-emp')->first();

        if (! $empSubModule) {
            throw new RuntimeException('Employee Management submodule with code "hr-emp" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'الموظفون', 'en' => 'Employees'],
                'description' => ['ar' => 'تعريف الموظفين وربطهم بالهيكل التنظيمي والوظائف.', 'en' => 'Define employees and link them to the organization structure and job assignments.'],
                'code' => 'hr-emp-emp',
                'route' => 'hr.employee-management.employees',
                'icon' => 'identification',
                'color' => 'sky',
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($empSubModule): array {
            foreach (['name', 'description'] as $attribute) {
                $application[$attribute] = json_encode($application[$attribute], JSON_UNESCAPED_UNICODE);
            }

            $application['submodule_id'] = $empSubModule->id;

            return $application;
        }, $applications);

        Application::query()->upsert(
            $prepared,
            ['code'],
            ['name', 'description', 'route', 'icon', 'color', 'sort_order', 'permission_name', 'permission_group', 'custom_actions', 'is_active', 'submodule_id'],
        );
    }
}
