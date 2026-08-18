<?php

namespace Modules\Inventory\Database\Seeders\System;

use App\Services\NavigationTreeService;
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
        $inventoryModule = Module::where('code', 'inv')->first();

        if (! $inventoryModule) {
            throw new RuntimeException('Inventory module with code "inv" was not found.');
        }

        $inventorySubModules = [
            [
                'name' => ['ar' => 'إدارة المخازن', 'en' => 'Warehousing'],
                'description' => ['ar' => 'المخازن ومواقع التخزين والجرد الدوري وسطوره.', 'en' => 'Warehouses, storage locations, cycle counts, and their lines.'],
                'code' => 'inv-whs',
                'route' => 'inventory.warehousing',
                'icon' => 'building-storefront',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'حركات المخزون', 'en' => 'Stock Movements'],
                'description' => ['ar' => 'حركات المخزون والدفعات والتخصيصات.', 'en' => 'Inventory movements, batch lots, and allocations.'],
                'code' => 'inv-stk',
                'route' => 'inventory.stock-movements',
                'icon' => 'arrows-right-left',
                'sort_order' => 1,
                'permission_group' => null,
                'is_active' => true,
            ],
        ];

        $subModules = array_map(function (array $subModule) use ($inventoryModule): array {
            $subModule = $this->encodeTranslatableAttributes($subModule);
            $subModule['module_id'] = $inventoryModule->id;

            return $subModule;
        }, $inventorySubModules);

        SubModule::query()->upsert(
            $subModules,
            ['code'],
            ['name', 'description', 'route', 'icon', 'sort_order', 'permission_group', 'is_active', 'module_id'],
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
        foreach (['name', 'description'] as $attribute) {
            if (isset($payload[$attribute]) && is_array($payload[$attribute])) {
                $payload[$attribute] = json_encode($payload[$attribute], JSON_UNESCAPED_UNICODE);
            }
        }

        return $payload;
    }
}
