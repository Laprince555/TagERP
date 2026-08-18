<?php

namespace Modules\Finance\Database\Seeders\AccountsPayable;

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
        $accountsPayable = SubModule::where('code', 'fin-ap')->first();

        if (! $accountsPayable) {
            throw new RuntimeException('Accounts Payable submodule with code "fin-ap" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'الملفات المالية للموردين', 'en' => 'Vendor Profiles'],
                'description' => ['ar' => 'العملة والحساب الدائن وشروط السداد لكل دور مالي للمورد.', 'en' => 'The currency, control account, and payment terms for each of a vendor\'s financial roles.'],
                'code' => 'fin-ap-vpr',
                'route' => 'finance.accounts-payable.vendor-profiles',
                'icon' => 'identification',
                'color' => 'orange',
                'application_group' => ['ar' => 'تطبيقات الموردين', 'en' => 'Vendor Applications'],
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'فواتير الموردين', 'en' => 'Invoices'],
                'description' => ['ar' => 'استلام فواتير الموردين وترحيلها عبر دورة الموافقة حتى التفعيل.', 'en' => 'Intake vendor invoices and carry them through approval to activation.'],
                'code' => 'fin-ap-inv',
                'route' => 'finance.accounts-payable.invoices',
                'icon' => 'document-text',
                'color' => 'rose',
                'application_group' => ['ar' => 'تطبيقات الفواتير', 'en' => 'Invoice Applications'],
                'sort_order' => 1,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => json_encode(['submit', 'approve', 'reject', 'activate']),
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'تصنيفات الخصومات', 'en' => 'Deduction Categories'],
                'description' => ['ar' => 'أشكال الخصومات المطبقة على فواتير الموردين.', 'en' => 'The shapes of deductions applied to vendor invoices.'],
                'code' => 'fin-ap-dcc',
                'route' => 'finance.accounts-payable.deduction-categories',
                'icon' => 'minus-circle',
                'color' => 'amber',
                'application_group' => ['ar' => 'تطبيقات الخصومات', 'en' => 'Deduction Applications'],
                'sort_order' => 2,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'أنواع الفواتير', 'en' => 'Invoice Types'],
                'description' => ['ar' => 'التصنيفات القابلة للتعريف لمحتوى فاتورة المورد (مواد، خدمات، فاتورة غير خاضعة للضريبة، ...).', 'en' => 'User-defined classifications of an AP invoice\'s content (material, service, untaxed invoice, ...).'],
                'code' => 'fin-ap-ivt',
                'route' => 'finance.accounts-payable.invoice-types',
                'icon' => 'tag',
                'color' => 'rose',
                'application_group' => ['ar' => 'تطبيقات الفواتير', 'en' => 'Invoice Applications'],
                'sort_order' => 5,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الخصومات', 'en' => 'Deductions'],
                'description' => ['ar' => 'الخصومات القابلة للتطبيق على فواتير الموردين، بقيمة ثابتة أو نسبة.', 'en' => 'Deductions applicable on vendor invoices, as a fixed amount or a percentage.'],
                'code' => 'fin-ap-ddc',
                'route' => 'finance.accounts-payable.deductions',
                'icon' => 'minus-circle',
                'color' => 'amber',
                'application_group' => ['ar' => 'تطبيقات الخصومات', 'en' => 'Deduction Applications'],
                'sort_order' => 3,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'ربط الخصومات بالحسابات', 'en' => 'Deduction GL Links'],
                'description' => ['ar' => 'الحساب المحاسبي الذي يُرحَّل إليه كل خصم أو تصنيف خصم.', 'en' => 'The GL account each deduction or deduction category posts to.'],
                'code' => 'fin-ap-dgl',
                'route' => 'finance.accounts-payable.deduction-gl-links',
                'icon' => 'link',
                'color' => 'amber',
                'application_group' => ['ar' => 'تطبيقات الخصومات', 'en' => 'Deduction Applications'],
                'sort_order' => 4,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($accountsPayable): array {
            $application = $this->encodeTranslatableAttributes($application);
            $application['submodule_id'] = $accountsPayable->id;

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
