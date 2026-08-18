<?php

namespace Modules\Inventory\Database\Seeders\StockMovements;

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
        $stockMovements = SubModule::where('code', 'inv-stk')->first();

        if (! $stockMovements) {
            throw new RuntimeException('Stock Movements submodule with code "inv-stk" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'حركات المخزون', 'en' => 'InventoryMovement'],
                'description' => ['ar' => 'تسجيل حركات إدخال وإخراج ونقل المخزون.', 'en' => 'Record inventory in, out, and transfer movements.'],
                'code' => 'inv-stk-mov',
                'route' => 'inventory.stock-movements.movements',
                'icon' => 'arrows-right-left',
                'color' => 'sky',
                'application_group' => ['ar' => 'تطبيقات حركات المخزون', 'en' => 'Stock Movements Applications'],
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => json_encode([]),
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الدفعات', 'en' => 'BatchLot'],
                'description' => ['ar' => 'إدارة دفعات وأرقام تشغيل الأصناف.', 'en' => 'Manage item batch and lot numbers.'],
                'code' => 'inv-stk-bat',
                'route' => 'inventory.stock-movements.batch-lots',
                'icon' => 'queue-list',
                'color' => 'violet',
                'application_group' => ['ar' => 'تطبيقات حركات المخزون', 'en' => 'Stock Movements Applications'],
                'sort_order' => 1,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => json_encode([]),
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'التخصيصات', 'en' => 'Allocation'],
                'description' => ['ar' => 'تخصيص المخزون للطلبات والمستندات.', 'en' => 'Allocate stock against orders and documents.'],
                'code' => 'inv-stk-alc',
                'route' => 'inventory.stock-movements.allocations',
                'icon' => 'lock-closed',
                'color' => 'rose',
                'application_group' => ['ar' => 'تطبيقات حركات المخزون', 'en' => 'Stock Movements Applications'],
                'sort_order' => 2,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => json_encode([]),
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($stockMovements): array {
            $application = $this->encodeTranslatableAttributes($application);
            $application['submodule_id'] = $stockMovements->id;

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
