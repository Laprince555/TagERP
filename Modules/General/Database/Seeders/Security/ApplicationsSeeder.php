<?php

namespace Modules\General\Database\Seeders\Security;

use App\Services\NavigationTreeService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\General\Database\Seeders\System\Concerns\EncodesTranslatableAttributes;
use Modules\General\System\Application;
use Modules\General\System\SubModule;
use RuntimeException;

class ApplicationsSeeder extends Seeder
{
    use EncodesTranslatableAttributes;
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $securitySubModule = SubModule::where('code', 'gen-sec')->first();

        if (! $securitySubModule) {
            throw new RuntimeException('Security & Roles submodule with code "gen-sec" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'الصلاحيات', 'en' => 'Permissions'],
                'description' => ['ar' => 'كتالوج مرجعي لكل الصلاحيات المتولّدة في النظام.', 'en' => 'A reference catalog of every permission generated across the system.'],
                'code' => 'gen-sec-per',
                'route' => 'general.security.permissions',
                'icon' => 'key',
                'color' => 'slate',
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => json_encode(['assign', 'unassign']),
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الأدوار', 'en' => 'Roles'],
                'description' => ['ar' => 'حزم صلاحيات مسمّاة (زي "مدير مالي") تُربط بلقب وظيفي أو موظف معيّن.', 'en' => 'Named bundles of permissions (e.g. "Finance Manager") attached to a job title or a specific employee.'],
                'code' => 'gen-sec-rol',
                'route' => 'general.security.roles',
                'icon' => 'shield-check',
                'color' => 'rose',
                'sort_order' => 1,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => json_encode(['assign']),
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($securitySubModule): array {
            $application = $this->encodeTranslatableAttributes($application);
            $application['submodule_id'] = $securitySubModule->id;

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
