<?php

namespace Modules\Procurement\Database\Seeders\Vendors;

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
        $vendorManagement = SubModule::where('code', 'proc-ven')->first();

        if (! $vendorManagement) {
            throw new RuntimeException('Vendor Management submodule with code "proc-ven" was not found.');
        }

        $applications = [
            [
                'name' => ['ar' => 'الموردون', 'en' => 'Vendors'],
                'description' => ['ar' => 'تصنيف الشركات كموردين وتحديد نوعهم وعملتهم الافتراضية.', 'en' => 'Classify companies as vendors and set their type and default currency.'],
                'code' => 'proc-ven-vnd',
                'route' => 'procurement.vendor-management.vendors',
                'icon' => 'truck',
                'color' => 'teal',
                'application_group' => ['ar' => 'تطبيقات الموردين', 'en' => 'Vendor Applications'],
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'custom_actions' => null,
                'is_active' => true,
            ],
        ];

        $prepared = array_map(function (array $application) use ($vendorManagement): array {
            $application = $this->encodeTranslatableAttributes($application);
            $application['submodule_id'] = $vendorManagement->id;

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
