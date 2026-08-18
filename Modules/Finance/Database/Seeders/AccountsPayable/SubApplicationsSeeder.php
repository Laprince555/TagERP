<?php

namespace Modules\Finance\Database\Seeders\AccountsPayable;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubApplicationsSeeder extends Seeder
{
    public function run(): void
    {
        $invoiceApplicationId = DB::table('applications')->where('code', 'fin-ap-inv')->value('id');

        DB::table('sub_applications')->insertOrIgnore([
            [
                'application_id' => $invoiceApplicationId,
                'code' => 'fin-ap-inv-lin',
                'name' => json_encode(['ar' => 'سطور الفاتورة', 'en' => 'Invoice Lines']),
                'description' => json_encode(['ar' => 'سطور الأصناف/الخدمات على فاتورة المورد', 'en' => 'Item/service lines on a vendor invoice']),
                'route' => null,
                'icon' => 'list-bullet',
                'color' => '#6366F1',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'application_id' => $invoiceApplicationId,
                'code' => 'fin-ap-inv-txl',
                'name' => json_encode(['ar' => 'سطور الضريبة', 'en' => 'Tax Lines']),
                'description' => json_encode(['ar' => 'سطور الضرائب المطبقة على فاتورة المورد', 'en' => 'Tax lines applied on a vendor invoice']),
                'route' => null,
                'icon' => 'receipt-percent',
                'color' => '#F59E0B',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'application_id' => $invoiceApplicationId,
                'code' => 'fin-ap-inv-ddl',
                'name' => json_encode(['ar' => 'سطور الاستقطاع', 'en' => 'Deduction Lines']),
                'description' => json_encode(['ar' => 'سطور الاستقطاعات المطبقة على فاتورة المورد', 'en' => 'Deduction lines applied on a vendor invoice']),
                'route' => null,
                'icon' => 'minus-circle',
                'color' => '#EF4444',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
