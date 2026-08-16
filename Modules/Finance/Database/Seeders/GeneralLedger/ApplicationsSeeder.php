<?php

namespace Modules\Finance\Database\Seeders\GeneralLedger;

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
        $generalLedger = SubModule::where('code', 'fin-gl')->first();

        if (! $generalLedger) {
            throw new RuntimeException('General Ledger submodule with code "fin-gl" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'تصنيفات الحسابات', 'en' => 'Account Categories'],
                'description' => ['ar' => 'تصنيف الحسابات حسب طبيعتها وتحديد موقعها في القوائم المالية.', 'en' => 'Classify accounts by nature and place them on the financial statements.'],
                'code' => 'fin-gl-cat',
                'route' => 'finance.general-ledger.account-categories',
                'icon' => 'tag',
                'color' => 'amber',
                'application_group' => ['ar' => 'تطبيقات الحسابات', 'en' => 'Accounts Applications'],
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الحسابات', 'en' => 'Accounts'],
                'description' => ['ar' => 'كتالوج الحسابات العام وهيكله الشجري المشترك بين كل شجرات الحسابات.', 'en' => 'The group-wide account catalog and the shared tree every chart draws from.'],
                'code' => 'fin-gl-acc',
                'route' => 'finance.general-ledger.accounts',
                'icon' => 'list-bullet',
                'color' => 'emerald',
                'application_group' => ['ar' => 'تطبيقات الحسابات', 'en' => 'Accounts Applications'],
                'sort_order' => 1,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'شجرات الحسابات', 'en' => 'Charts of Accounts'],
                'description' => ['ar' => 'تعريف شجرات الحسابات واختيار الحسابات المشمولة في كل شجرة.', 'en' => 'Define charts of accounts and pick which accounts each one includes.'],
                'code' => 'fin-gl-coa',
                'route' => 'finance.general-ledger.charts',
                'icon' => 'squares-2x2',
                'color' => 'sky',
                'application_group' => ['ar' => 'تطبيقات الحسابات', 'en' => 'Accounts Applications'],
                'sort_order' => 2,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'أسعار الصرف', 'en' => 'Exchange Rates'],
                'description' => ['ar' => 'أسعار صرف العملات اليومية وأسعار الإقفال والمتوسط.', 'en' => 'Daily, closing, and average currency conversion rates.'],
                'code' => 'fin-gl-rat',
                'route' => 'finance.general-ledger.exchange-rates',
                'icon' => 'arrows-right-left',
                'color' => 'cyan',
                'application_group' => ['ar' => 'تطبيقات الدفاتر والفترات', 'en' => 'Ledgers & Periods Applications'],
                'sort_order' => 3,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'السنوات المالية', 'en' => 'Fiscal Years'],
                'description' => ['ar' => 'تعريف السنوات المالية وتوليد فتراتها وفترة التسوية.', 'en' => 'Define fiscal years and generate their periods and adjustment period.'],
                'code' => 'fin-gl-fyr',
                'route' => 'finance.general-ledger.fiscal-years',
                'icon' => 'calendar-days',
                'color' => 'indigo',
                'application_group' => ['ar' => 'تطبيقات الدفاتر والفترات', 'en' => 'Ledgers & Periods Applications'],
                'sort_order' => 4,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'دفاتر الأستاذ', 'en' => 'Ledgers'],
                'description' => ['ar' => 'الأوعية التي تحفظ فيها الأرصدة: شجرة وعملة وكيان لكل دفتر.', 'en' => 'The containers balances live in: a chart, a currency, and an entity each.'],
                'code' => 'fin-gl-led',
                'route' => 'finance.general-ledger.ledgers',
                'icon' => 'circle-stack',
                'color' => 'sky',
                'application_group' => ['ar' => 'تطبيقات الدفاتر والفترات', 'en' => 'Ledgers & Periods Applications'],
                'sort_order' => 5,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'دفاتر اليومية', 'en' => 'Journal Books'],
                'description' => ['ar' => 'أنواع المستندات: سند قبض، سند صرف، قيد يومية عام، وترقيم كل منها.', 'en' => 'Document kinds — receipt, payment, general journal — and how each is numbered.'],
                'code' => 'fin-gl-bok',
                'application_group' => ['ar' => 'تطبيقات اليومية', 'en' => 'Journal Applications'],
                'route' => 'finance.general-ledger.journal-books',
                'icon' => 'book-open',
                'color' => 'rose',
                'application_group' => ['ar' => 'تطبيقات الدفاتر والفترات', 'en' => 'Ledgers & Periods Applications'],
                'sort_order' => 6,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'القيود اليومية', 'en' => 'Journals'],
                'description' => ['ar' => 'إدخال القيود وترحيلها وعكسها، وسطورها وتحليلاتها ومراجعها.', 'en' => 'Enter, post, and reverse journals along with their lines, analyses, and references.'],
                'code' => 'fin-gl-jou',
                'route' => 'finance.general-ledger.journals',
                'icon' => 'pencil-square',
                'color' => 'violet',
                'application_group' => ['ar' => 'تطبيقات القيود', 'en' => 'Journal Applications'],
                'sort_order' => 7,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'مراكز التكلفة', 'en' => 'Cost Centres'],
                'description' => ['ar' => 'شجرة مراكز التكلفة وتحديد أي منها يقبل الحركات.', 'en' => 'The cost centre tree and which of them accept transactions.'],
                'code' => 'fin-gl-cct',
                'route' => 'finance.general-ledger.cost-centers',
                'icon' => 'rectangle-group',
                'color' => 'orange',
                'application_group' => ['ar' => 'تطبيقات القيود', 'en' => 'Journal Applications'],
                'sort_order' => 8,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'مجموعات الحسابات', 'en' => 'Account Groups'],
                'description' => ['ar' => 'مجموعات حسابات تُمنح للموظفين أو للمسميات الوظيفية للتحكم في الرؤية.', 'en' => 'Sets of accounts granted to employees or job titles to control what they may see.'],
                'code' => 'fin-gl-agr',
                'route' => 'finance.general-ledger.account-groups',
                'icon' => 'user-group',
                'color' => 'slate',
                'application_group' => ['ar' => 'تطبيقات الحسابات', 'en' => 'Accounts Applications'],
                'sort_order' => 9,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($generalLedger): array {
            $application = $this->encodeTranslatableAttributes($application);
            $application['submodule_id'] = $generalLedger->id;

            return $application;
        }, $applications);

        Application::query()->upsert(
            $prepared,
            ['code'],
            ['name', 'description', 'route', 'icon', 'color', 'application_group', 'sort_order', 'permission_name', 'permission_group', 'custom_actions', 'is_active', 'submodule_id'],
        );
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
