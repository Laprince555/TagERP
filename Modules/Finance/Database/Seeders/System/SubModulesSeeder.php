<?php

namespace Modules\Finance\Database\Seeders\System;

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
        $financeModule = Module::where('code', 'fin')->first();

        if (! $financeModule) {
            throw new RuntimeException('Finance module with code "fin" was not found.');
        }

        $financeSubModules = [
            [
                'name' => ['ar' => 'الأستاذ العام', 'en' => 'General Ledger'],
                'description' => ['ar' => 'شجرة الحسابات والقيود اليومية والفترات المالية وأرصدة الأستاذ.', 'en' => 'Chart of accounts, journal entries, fiscal periods, and ledger balances.'],
                'code' => 'fin-gl',
                'route' => 'finance.general-ledger',
                'icon' => 'book-open',
                'sort_order' => 0,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الحسابات الدائنة', 'en' => 'Accounts Payable'],
                'description' => ['ar' => 'فواتير الموردين والمدفوعات وأعمار الذمم الدائنة.', 'en' => 'Vendor invoices, payments, and payables aging.'],
                'code' => 'fin-ap',
                'route' => 'finance.accounts-payable',
                'icon' => 'arrow-up-circle',
                'sort_order' => 1,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الحسابات المدينة', 'en' => 'Accounts Receivable'],
                'description' => ['ar' => 'فواتير العملاء والمقبوضات وأعمار الذمم المدينة.', 'en' => 'Customer invoices, receipts, and receivables aging.'],
                'code' => 'fin-ar',
                'route' => 'finance.accounts-receivable',
                'icon' => 'arrow-down-circle',
                'sort_order' => 2,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'إدارة الضرائب', 'en' => 'Tax Management'],
                'description' => ['ar' => 'أكواد الضريبة ومعدلاتها والإقرارات الضريبية.', 'en' => 'Tax codes, rates, and tax return filings.'],
                'code' => 'fin-tax',
                'route' => 'finance.tax-management',
                'icon' => 'receipt-percent',
                'sort_order' => 3,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'إدارة الأصول', 'en' => 'Assets Management'],
                'description' => ['ar' => 'الأصول الثابتة والإهلاك وعمليات الاستبعاد.', 'en' => 'Fixed assets, depreciation, and disposals.'],
                'code' => 'fin-ast',
                'route' => 'finance.assets-management',
                'icon' => 'cube',
                'sort_order' => 4,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'التنبؤات والموازنات', 'en' => 'Forecasting & Budgeting'],
                'description' => ['ar' => 'إعداد الموازنات والتنبؤات المالية ومقارنة المخطط بالفعلي.', 'en' => 'Budget preparation, financial forecasts, and plan versus actual comparison.'],
                'code' => 'fin-bdg',
                'route' => 'finance.budgeting',
                'icon' => 'chart-pie',
                'sort_order' => 5,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'النقدية والبنوك', 'en' => 'Cash & Banks'],
                'description' => ['ar' => 'الخزائن والحسابات البنكية والتسويات البنكية والتحويلات.', 'en' => 'Cash boxes, bank accounts, bank reconciliation, and transfers.'],
                'code' => 'fin-csh',
                'route' => 'finance.cash-and-banks',
                'icon' => 'building-library',
                'sort_order' => 6,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الصناديق والتمويل', 'en' => 'Funds & Finance'],
                'description' => ['ar' => 'الصناديق والقروض والتسهيلات ومصادر التمويل.', 'en' => 'Funds, loans, credit facilities, and financing sources.'],
                'code' => 'fin-fnd',
                'route' => 'finance.funds-and-finance',
                'icon' => 'currency-dollar',
                'sort_order' => 7,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'التقارير المالية', 'en' => 'Financial Reports'],
                'description' => ['ar' => 'الميزانية العمومية وقائمة الدخل والتدفقات النقدية وميزان المراجعة.', 'en' => 'Balance sheet, income statement, cash flow, and trial balance.'],
                'code' => 'fin-rpt',
                'route' => 'finance.reports',
                'icon' => 'document-chart-bar',
                'sort_order' => 8,
                'permission_group' => null,
                'is_active' => true,
            ],
        ];

        $subModules = array_map(function (array $subModule) use ($financeModule): array {
            $subModule = $this->encodeTranslatableAttributes($subModule);
            $subModule['module_id'] = $financeModule->id;

            return $subModule;
        }, $financeSubModules);

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
