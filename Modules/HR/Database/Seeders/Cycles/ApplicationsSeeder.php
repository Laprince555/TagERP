<?php

namespace Modules\HR\Database\Seeders\Cycles;

use App\Services\NavigationTreeService;
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
        $cyclesSubModule = SubModule::where('code', 'hr-cyc')->first();

        if (! $cyclesSubModule) {
            throw new RuntimeException('Cycles submodule with code "hr-cyc" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'أنواع دورات الاعتماد', 'en' => 'Cycle Types'],
                'description' => ['ar' => 'تصنيف دورات الاعتماد وربطها بالتطبيق المصدر للمستند.', 'en' => 'Classify cycles and which application creates their subject documents.'],
                'code' => 'hr-cyc-typ',
                'route' => 'hr.cycles.cycle-types',
                'icon' => 'tag',
                'color' => 'sky',
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'دورات الاعتماد', 'en' => 'Cycles'],
                'description' => ['ar' => 'قوالب سير الاعتماد القابلة لإعادة الاستخدام ومراحلها.', 'en' => 'Reusable approval-workflow templates and their stages.'],
                'code' => 'hr-cyc-cyc',
                'route' => 'hr.cycles.cycles',
                'icon' => 'arrow-path',
                'color' => 'indigo',
                'sort_order' => 1,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'معاملات دورات الاعتماد', 'en' => 'Cycle Transactions'],
                'description' => ['ar' => 'متابعة تنفيذ دورات الاعتماد على المستندات الفعلية.', 'en' => 'Track approval workflows running against real documents.'],
                'code' => 'hr-cyc-trx',
                'route' => 'hr.cycles.transactions',
                'icon' => 'check-badge',
                'color' => 'emerald',
                'sort_order' => 2,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($cyclesSubModule): array {
            foreach (['name', 'description'] as $attribute) {
                $application[$attribute] = json_encode($application[$attribute], JSON_UNESCAPED_UNICODE);
            }

            $application['submodule_id'] = $cyclesSubModule->id;

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
