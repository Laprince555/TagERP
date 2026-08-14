<?php

use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\System\Module;
use Modules\General\System\SubModule;

/**
 * Seed the module rows exactly as the superseded seeder left them.
 */
function seedLegacyModules(): void
{
    Module::factory()->create([
        'code' => 'sal',
        'route' => 'sales',
        'name' => ['ar' => 'المبيعات', 'en' => 'Sales'],
        'description' => ['ar' => 'إدارة المبيعات', 'en' => 'Manage sales'],
        'icon' => 'currency-dollar',
        'sort_order' => 0,
    ]);

    Module::factory()->create([
        'code' => 'ven',
        'route' => 'vendors',
        'name' => ['ar' => 'الموردون', 'en' => 'Vendors'],
        'description' => ['ar' => 'إدارة الموردين', 'en' => 'Manage vendors'],
        'icon' => 'building-storefront',
        'sort_order' => 0,
    ]);

    Module::factory()->create([
        'code' => 'inv',
        'route' => 'inventory',
        'name' => ['ar' => 'المخزون', 'en' => 'Inventory'],
        'icon' => 'archive-box',
        'sort_order' => 0,
    ]);
}

it('replaces the legacy sales and vendors modules with their canonical identities', function (): void {
    seedLegacyModules();

    (new ModulesSeeder)->run();

    expect(Module::where('code', 'sal')->exists())->toBeFalse()
        ->and(Module::where('route', 'sales')->exists())->toBeFalse()
        ->and(Module::where('code', 'ven')->exists())->toBeFalse()
        ->and(Module::where('route', 'vendors')->exists())->toBeFalse();

    expect(Module::where('code', 'crm')->count())->toBe(1)
        ->and(Module::where('code', 'proc')->count())->toBe(1);
});

it('keeps the canonical module route names free of an index suffix', function (): void {
    seedLegacyModules();

    (new ModulesSeeder)->run();

    expect(Module::where('code', 'crm')->value('route'))->toBe('crm')
        ->and(Module::where('code', 'proc')->value('route'))->toBe('procurement');

    expect(Module::pluck('route')->all())
        ->each(fn ($route) => $route->not->toContain('.index'));
});

it('preserves the legacy record ids so existing relationships stay valid', function (): void {
    seedLegacyModules();

    $salesModule = Module::where('code', 'sal')->firstOrFail();
    $vendorsModule = Module::where('code', 'ven')->firstOrFail();

    $salesSubModule = SubModule::factory()->create(['module_id' => $salesModule->id]);

    (new ModulesSeeder)->run();

    expect(Module::where('code', 'crm')->value('id'))->toBe($salesModule->id)
        ->and(Module::where('code', 'proc')->value('id'))->toBe($vendorsModule->id)
        ->and($salesSubModule->fresh()->module_id)->toBe($salesModule->id);
});

it('applies the canonical translated attributes to the corrected records', function (): void {
    seedLegacyModules();

    (new ModulesSeeder)->run();

    $crmModule = Module::where('code', 'crm')->firstOrFail();

    expect($crmModule->getTranslation('name', 'en'))->toBe('CRM')
        ->and($crmModule->getTranslation('name', 'ar'))->toBe('إدارة العلاقات والعملاء')
        ->and($crmModule->icon)->toBe('shopping-cart')
        ->and($crmModule->sort_order)->toBe(2)
        ->and(Module::where('code', 'crm')->count())->toBe(1);

    $procurementModule = Module::where('code', 'proc')->firstOrFail();

    expect($procurementModule->getTranslation('name', 'en'))->toBe('Procurement')
        ->and($procurementModule->getTranslation('name', 'ar'))->toBe('المشتريات والتوريد')
        ->and($procurementModule->icon)->toBe('truck')
        ->and($procurementModule->sort_order)->toBe(3);
});

it('creates no duplicates when the seeder runs repeatedly', function (): void {
    seedLegacyModules();

    (new ModulesSeeder)->run();

    $moduleCount = Module::count();

    (new ModulesSeeder)->run();
    (new ModulesSeeder)->run();

    expect(Module::count())->toBe($moduleCount)
        ->and(Module::where('code', 'crm')->count())->toBe(1)
        ->and(Module::where('code', 'proc')->count())->toBe(1);
});

it('merges the legacy record into the canonical one when both already exist', function (): void {
    seedLegacyModules();

    $salesModule = Module::where('code', 'sal')->firstOrFail();
    $salesSubModule = SubModule::factory()->create(['module_id' => $salesModule->id]);

    $existingCrmModule = Module::factory()->create(['code' => 'crm', 'route' => 'crm']);

    (new ModulesSeeder)->run();

    expect(Module::where('code', 'crm')->count())->toBe(1)
        ->and(Module::where('code', 'sal')->exists())->toBeFalse()
        ->and($salesSubModule->fresh()->module_id)->toBe($existingCrmModule->id);
});

it('preserves the inventory record id while applying its canonical metadata', function (): void {
    seedLegacyModules();

    $inventoryModule = Module::where('code', 'inv')->firstOrFail();

    (new ModulesSeeder)->run();

    expect(Module::where('code', 'inv')->count())->toBe(1);

    $inventoryModule->refresh();

    expect($inventoryModule->code)->toBe('inv')
        ->and($inventoryModule->route)->toBe('inventory')
        ->and($inventoryModule->getTranslation('name', 'en'))->toBe('Inventory')
        ->and($inventoryModule->getTranslation('name', 'ar'))->toBe('المخزون')
        ->and($inventoryModule->getTranslation('description', 'en'))->toBe('Manage inventory, warehouses, stock movements, and item availability.')
        ->and($inventoryModule->getTranslation('description', 'ar'))->toBe('إدارة المخزون والمستودعات وحركات الأصناف وتوافرها.')
        ->and($inventoryModule->icon)->toBe('archive-box')
        ->and($inventoryModule->sort_order)->toBe(4);
});

it('seeds the canonical module order', function (): void {
    seedLegacyModules();

    (new ModulesSeeder)->run();

    $sortOrders = Module::query()->pluck('sort_order', 'code')->sortKeys()->all();

    expect($sortOrders)->toBe([
        'crm' => 2,
        'fin' => 0,
        'gen' => 6,
        'hr' => 1,
        'inv' => 4,
        'prj' => 5,
        'proc' => 3,
    ]);
});

it('seeds every canonical module exactly once', function (): void {
    seedLegacyModules();

    (new ModulesSeeder)->run();
    (new ModulesSeeder)->run();

    foreach (['fin', 'hr', 'crm', 'proc', 'inv', 'prj', 'gen'] as $code) {
        expect(Module::where('code', $code)->count())->toBe(1);
    }

    expect(Module::count())->toBe(7);
});
