<?php

namespace Modules\Inventory\Database\Seeders\Warehousing;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubApplicationsSeeder extends Seeder
{
    public function run(): void
    {
        $warehouseApplicationId = DB::table('applications')->where('code', 'inv-whs-wrh')->value('id');
        $cycleCountApplicationId = DB::table('applications')->where('code', 'inv-whs-ccn')->value('id');

        DB::table('sub_applications')->insertOrIgnore([
            [
                'application_id' => $warehouseApplicationId,
                'code' => 'inv-whs-wrh-loc',
                'name' => json_encode(['ar' => 'مواقع التخزين', 'en' => 'WarehouseLocation']),
                'description' => json_encode(['ar' => 'إدارة مواقع التخزين داخل المخزن', 'en' => 'Manage storage locations within the warehouse']),
                'route' => 'inventory.warehousing.warehouses.locations',
                'icon' => 'map-pin',
                'color' => '#10B981',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'application_id' => $cycleCountApplicationId,
                'code' => 'inv-whs-ccn-lin',
                'name' => json_encode(['ar' => 'سطور الجرد الدوري', 'en' => 'CycleCountLine']),
                'description' => json_encode(['ar' => 'إدارة سطور عمليات الجرد الدوري', 'en' => 'Manage cycle count line items']),
                'route' => 'inventory.warehousing.cycle-counts.lines',
                'icon' => 'list-bullet',
                'color' => '#F59E0B',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
