<?php

namespace Modules\Finance\Database\Seeders\CashAndBanks;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApplicationsSeeder extends Seeder
{
    public function run(): void
    {
        $subModuleId = DB::table('sub_modules')->where('code', 'fin-cbn')->value('id');

        DB::table('applications')->insertOrIgnore([
            [
                'submodule_id' => $subModuleId,
                'code' => 'fin-cbn-cat',
                'name' => json_encode(['ar' => 'فئات البنوك', 'en' => 'Bank Categories']),
                'description' => json_encode(['ar' => 'تصنيفات البنوك', 'en' => 'Bank categories and classifications']),
                'route' => 'finance.cash-and-banks.bank-categories',
                'icon' => 'folder',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'submodule_id' => $subModuleId,
                'code' => 'fin-cbn-bnk',
                'name' => json_encode(['ar' => 'البنوك', 'en' => 'Banks']),
                'description' => json_encode(['ar' => 'إدارة الحسابات البنكية', 'en' => 'Manage bank accounts and details']),
                'route' => 'finance.cash-and-banks.banks',
                'icon' => 'building-office-2',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'submodule_id' => $subModuleId,
                'code' => 'fin-cbn-saf',
                'name' => json_encode(['ar' => 'الخزائن', 'en' => 'Safes']),
                'description' => json_encode(['ar' => 'إدارة الخزائن والنقد', 'en' => 'Manage safes and cash']),
                'route' => 'finance.cash-and-banks.safes',
                'icon' => 'vault',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'submodule_id' => $subModuleId,
                'code' => 'fin-cbn-iadj',
                'name' => json_encode(['ar' => 'التحويلات الداخلية', 'en' => 'Internal Adjustments']),
                'description' => json_encode(['ar' => 'تحويلات بين البنوك والخزائن', 'en' => 'Transfers between banks and safes']),
                'route' => 'finance.cash-and-banks.internal-adjust',
                'icon' => 'arrow-right-left',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'submodule_id' => $subModuleId,
                'code' => 'fin-cbn-bexp',
                'name' => json_encode(['ar' => 'مصاريف البنوك', 'en' => 'Bank Expenses']),
                'description' => json_encode(['ar' => 'الرسوم والفوائد والعمولات', 'en' => 'Fees, interest, and commissions']),
                'route' => 'finance.cash-and-banks.bank-expenses',
                'icon' => 'receipt',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'submodule_id' => $subModuleId,
                'code' => 'fin-cbn-pdr',
                'name' => json_encode(['ar' => 'طلبات الصرف', 'en' => 'Payment Disbursements']),
                'description' => json_encode(['ar' => 'إدارة طلبات صرف الأموال', 'en' => 'Manage payment disbursement requests']),
                'route' => 'finance.cash-and-banks.payment-disbursements',
                'icon' => 'money',
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'submodule_id' => $subModuleId,
                'code' => 'fin-cbn-col',
                'name' => json_encode(['ar' => 'طلبات التحصيل', 'en' => 'Collection Requests']),
                'description' => json_encode(['ar' => 'إدارة طلبات التحصيل من العملاء', 'en' => 'Manage customer collection requests']),
                'route' => 'finance.cash-and-banks.collection-requests',
                'icon' => 'inbox',
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
