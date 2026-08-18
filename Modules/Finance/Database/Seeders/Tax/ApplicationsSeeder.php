<?php

namespace Modules\Finance\Database\Seeders\Tax;

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
        $taxManagement = SubModule::where('code', 'fin-tax')->first();

        if (! $taxManagement) {
            throw new RuntimeException('Tax Management submodule with code "fin-tax" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'تصنيفات الضرائب', 'en' => 'Tax Categories'],
                'description' => ['ar' => 'التقسيمات الأساسية للضرائب المرتبطة بكل دولة.', 'en' => 'The basic tax classifications, scoped per country.'],
                'code' => 'fin-tax-cat',
                'route' => 'finance.tax-management.tax-categories',
                'icon' => 'tag',
                'color' => 'teal',
                'application_group' => ['ar' => 'تطبيقات الضرائب', 'en' => 'Tax Applications'],
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الضرائب', 'en' => 'Taxes'],
                'description' => ['ar' => 'الضرائب المسمّاة بمعدلاتها، وهل تضاف للمستند أم تُستقطع منه.', 'en' => 'Named taxes with their rates, and whether each adds to a document or is withheld from it.'],
                'code' => 'fin-tax-tax',
                'route' => 'finance.tax-management.taxes',
                'icon' => 'receipt-percent',
                'color' => 'teal',
                'application_group' => ['ar' => 'تطبيقات الضرائب', 'en' => 'Tax Applications'],
                'sort_order' => 1,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'ربط الضرائب بالحسابات', 'en' => 'Tax GL Links'],
                'description' => ['ar' => 'الحساب المحاسبي الذي تُرحَّل إليه كل ضريبة أو تصنيف ضريبي.', 'en' => 'The GL account each tax or tax category posts to.'],
                'code' => 'fin-tax-gll',
                'route' => 'finance.tax-management.tax-gl-links',
                'icon' => 'link',
                'color' => 'teal',
                'application_group' => ['ar' => 'تطبيقات الضرائب', 'en' => 'Tax Applications'],
                'sort_order' => 2,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'تسويات ضريبية', 'en' => 'Tax Adjustments'],
                'description' => ['ar' => 'تسويات على ضريبة معينة، نتيجة سداد أو نتائج فحص.', 'en' => 'Corrections against a tax, from a settlement or an inspection result.'],
                'code' => 'fin-tax-adj',
                'route' => 'finance.tax-management.tax-adjustments',
                'icon' => 'document-check',
                'color' => 'teal',
                'application_group' => ['ar' => 'تطبيقات الضرائب', 'en' => 'Tax Applications'],
                'sort_order' => 3,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($taxManagement): array {
            $application = $this->encodeTranslatableAttributes($application);
            $application['submodule_id'] = $taxManagement->id;

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
