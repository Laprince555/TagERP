<?php

namespace Modules\Inventory\Database\Seeders\Warehousing;

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
        $warehousing = SubModule::where('code', 'inv-whs')->first();

        if (! $warehousing) {
            throw new RuntimeException('Warehousing submodule with code "inv-whs" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'المخازن', 'en' => 'Warehouse'],
                'description' => ['ar' => 'تعريف المخازن ومواقعها.', 'en' => 'Define warehouses and their locations.'],
                'code' => 'inv-whs-wrh',
                'route' => 'inventory.warehousing.warehouses',
                'icon' => 'building-storefront',
                'color' => 'emerald',
                'application_group' => ['ar' => 'تطبيقات المخازن', 'en' => 'Warehousing Applications'],
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => json_encode([]),
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الجرد الدوري', 'en' => 'CycleCount'],
                'description' => ['ar' => 'عمليات الجرد الدوري وسطورها.', 'en' => 'Cycle count operations and their lines.'],
                'code' => 'inv-whs-ccn',
                'route' => 'inventory.warehousing.cycle-counts',
                'icon' => 'clipboard-document-check',
                'color' => 'amber',
                'application_group' => ['ar' => 'تطبيقات المخازن', 'en' => 'Warehousing Applications'],
                'sort_order' => 1,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => json_encode([]),
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($warehousing): array {
            $application = $this->encodeTranslatableAttributes($application);
            $application['submodule_id'] = $warehousing->id;

            return $application;
        }, $applications);

        Application::query()->upsert(
            $prepared,
            ['code'],
            ['name', 'description', 'route', 'icon', 'color', 'application_group', 'sort_order', 'permission_group', 'custom_actions', 'is_active', 'submodule_id'],
        );

        // `upsert` bypasses model events, so the navigation observer never fires for it.
        app(NavigationTreeService::class)->invalidateCache();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function encodeTranslatableAttributes(array $payload): array
    {
        foreach (['name', 'description', 'application_group'] as $attribute) {
            if (isset($payload[$attribute]) && is_array($payload[$attribute])) {
                $payload[$attribute] = json_encode($payload[$attribute], JSON_UNESCAPED_UNICODE);
            }
        }

        return $payload;
    }
}
