<?php

namespace Modules\Finance\Database\Seeders\GeneralLedger;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubApplicationsSeeder extends Seeder
{
    public function run(): void
    {
        $journalApplicationId = DB::table('applications')->where('code', 'fin-gl-jou')->value('id');

        DB::table('sub_applications')->insertOrIgnore([
            [
                'application_id' => $journalApplicationId,
                'code' => 'fin-gl-jou-lin',
                'name' => json_encode(['ar' => 'سطور القيد', 'en' => 'Journal Lines']),
                'description' => json_encode(['ar' => 'سطور المدين والدائن لقيد اليومية', 'en' => 'Debit and credit lines of a journal entry']),
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
