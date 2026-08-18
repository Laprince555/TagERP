<?php

namespace Modules\Procurement\Database\Seeders\System;

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
        $procurementModule = Module::where('code', 'proc')->first();

        if (! $procurementModule) {
            throw new RuntimeException('Procurement module with code "proc" was not found.');
        }

        $subModules = [
            [
                'name' => ['ar' => 'إدارة الموردين', 'en' => 'Vendor Management'],
                'description' => ['ar' => 'تصنيف الموردين وربطهم بالشركات المسجلة في النظام.', 'en' => 'Classify vendors and link them to companies already registered in the system.'],
                'code' => 'proc-ven',
                'route' => 'procurement.vendor-management',
                'icon' => 'building-storefront',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $subModule) use ($procurementModule): array {
            $subModule = $this->encodeTranslatableAttributes($subModule);
            $subModule['module_id'] = $procurementModule->id;

            return $subModule;
        }, $subModules);

        SubModule::query()->upsert(
            $prepared,
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
