<?php

namespace Modules\Finance\Database\Seeders\CashAndBanks;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubApplicationsSeeder extends Seeder
{
    public function run(): void
    {
        $paymentDisbursementApplicationId = DB::table('applications')->where('code', 'fin-cbn-pdr')->value('id');
        $collectionApplicationId = DB::table('applications')->where('code', 'fin-cbn-col')->value('id');

        DB::table('sub_applications')->insertOrIgnore([
            [
                'application_id' => $paymentDisbursementApplicationId,
                'code' => 'fin-cbn-pdr-det',
                'name' => json_encode(['ar' => 'تفاصيل طلب الصرف', 'en' => 'Disbursement Details']),
                'description' => json_encode(['ar' => 'سطور تفاصيل طلب صرف الأموال', 'en' => 'Detail lines of a payment disbursement request']),
                'route' => null,
                'icon' => 'list-bullet',
                'color' => '#6366F1',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'application_id' => $collectionApplicationId,
                'code' => 'fin-cbn-col-det',
                'name' => json_encode(['ar' => 'تفاصيل طلب التحصيل', 'en' => 'Collection Details']),
                'description' => json_encode(['ar' => 'سطور تفاصيل طلب التحصيل من العملاء', 'en' => 'Detail lines of a customer collection request']),
                'route' => null,
                'icon' => 'list-bullet',
                'color' => '#6366F1',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
