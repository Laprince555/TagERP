<?php

namespace Modules\General\Database\Seeders\World;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\General\Database\Seeders\System\Concerns\EncodesTranslatableAttributes;
use Modules\General\System\Application;
use Modules\General\System\SubModule;
use RuntimeException;

class WorldApplicationsSeeder extends Seeder
{
    use EncodesTranslatableAttributes;
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $worldSubModule = SubModule::where('code', 'gen-wld')->first();

        if (! $worldSubModule) {
            throw new RuntimeException('World submodule with code "gen-wld" was not found.');
        }

        $worldApplications = [
            [
                'name' => ['ar' => 'الدول', 'en' => 'Countries'],
                'description' => ['ar' => 'إدارة الدول ورموزها الدولية وبياناتها المرجعية.', 'en' => 'Manage countries, their international codes, and reference data.'],
                'code' => 'gen-wld-ctr',
                'route' => 'general.world.countries',
                'icon' => 'globe-alt',
                'color' => 'sky',
                'sort_order' => 0,
                'permission_name' => null,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الولايات والمناطق', 'en' => 'States'],
                'description' => ['ar' => 'إدارة الولايات والمناطق الإدارية التابعة للدول.', 'en' => 'Manage states and administrative regions belonging to countries.'],
                'code' => 'gen-wld-sta',
                'route' => 'general.world.states',
                'icon' => 'map',
                'color' => 'indigo',
                'sort_order' => 1,
                'permission_name' => null,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'المدن', 'en' => 'Cities'],
                'description' => ['ar' => 'إدارة المدن التابعة للولايات والمناطق.', 'en' => 'Manage cities belonging to states and regions.'],
                'code' => 'gen-wld-cty',
                'route' => 'general.world.cities',
                'icon' => 'building-office-2',
                'color' => 'violet',
                'sort_order' => 2,
                'permission_name' => null,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'المناطق الزمنية', 'en' => 'Time Zones'],
                'description' => ['ar' => 'إدارة المناطق الزمنية المرتبطة بالدول وفروق التوقيت.', 'en' => 'Manage time zones linked to countries and their UTC offsets.'],
                'code' => 'gen-wld-tzn',
                'route' => 'general.world.timezones',
                'icon' => 'clock',
                'color' => 'amber',
                'sort_order' => 3,
                'permission_name' => null,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'العملات', 'en' => 'Currencies'],
                'description' => ['ar' => 'إدارة العملات وأكوادها ورموزها المستخدمة في المعاملات.', 'en' => 'Manage currencies, their codes, and the symbols used in transactions.'],
                'code' => 'gen-wld-cur',
                'route' => 'general.world.currencies',
                'icon' => 'banknotes',
                'color' => 'emerald',
                'sort_order' => 4,
                'permission_name' => null,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'اللغات', 'en' => 'Languages'],
                'description' => ['ar' => 'إدارة اللغات المدعومة وأكوادها واتجاه الكتابة.', 'en' => 'Manage supported languages, their codes, and writing direction.'],
                'code' => 'gen-wld-lng',
                'route' => 'general.world.languages',
                'icon' => 'language',
                'color' => 'rose',
                'sort_order' => 5,
                'permission_name' => null,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الشركات', 'en' => 'Companies'],
                'description' => ['ar' => 'إدارة بيانات الشركات وارتباطها بالمواقع الجغرافية.', 'en' => 'Manage company records and how they map to geographic locations.'],
                'code' => 'gen-wld-com',
                'route' => 'general.world.companies',
                'icon' => 'building-office',
                'color' => 'cyan',
                'sort_order' => 6,
                'permission_name' => null,
                'permission_group' => null,
                'is_active' => true,
            ],
            [
                'name' => ['ar' => 'الأشخاص', 'en' => 'People'],
                'description' => ['ar' => 'إدارة بيانات الأشخاص وبيانات التواصل والعناوين.', 'en' => 'Manage person records along with their contact details and addresses.'],
                'code' => 'gen-wld-per',
                'route' => 'general.world.people',
                'icon' => 'user-group',
                'color' => 'orange',
                'sort_order' => 7,
                'permission_name' => null,
                'permission_group' => null,
                'is_active' => true,
            ],
        ];

        $applications = array_map(function (array $application) use ($worldSubModule): array {
            $application = $this->encodeTranslatableAttributes($application);
            $application['submodule_id'] = $worldSubModule->id;

            return $application;
        }, $worldApplications);

        Application::query()->upsert(
            $applications,
            ['code'],
            ['name', 'description', 'route', 'icon', 'color', 'sort_order', 'permission_name', 'permission_group', 'is_active', 'submodule_id'],
        );
    }
}
