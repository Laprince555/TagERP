<?php

use App\Livewire\DynamicRecordView\RelationPickerModal;
use App\Models\User;
use App\Support\DynamicRecordView\Resolution\RecordResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\General\Livewire\ApplicationsTable;
use Modules\General\System\Application;
use Modules\General\System\SubModule;
use Modules\General\System\SubModuleRecordView;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

afterEach(function (): void {
    Model::preventLazyLoading(false);
});

/**
 * Phase 10: RelationViewField::make('module.name') on SubModuleRecordView's
 * Basic Information tab reads a dotted relation path. Prove the record is
 * fetched with that relation eager-loaded (via
 * DynamicRecordView::requiredEagerLoads() -> RecordResolver::resolve()),
 * not lazy-loaded when Blade renders the field.
 */
it('eager loads dotted RelationViewField relations so rendering never lazy-loads', function (): void {
    $subModule = SubModule::factory()->create();

    Model::preventLazyLoading();

    $response = $this->get(route('general.sub-modules.view', ['recordId' => $subModule->id]));

    $response->assertOk()->assertSee($subModule->module->name);
});

it('declares module as a required eager load because of the module.name field', function (): void {
    $view = new SubModuleRecordView;

    expect($view->requiredEagerLoads())->toBe(['module']);
});

it('resolves the record with the relation already loaded, without an extra query', function (): void {
    $subModule = SubModule::factory()->create();

    DB::enableQueryLog();
    $resolved = app(RecordResolver::class)->resolve(new SubModuleRecordView, $subModule->id);
    $queryCount = count(DB::getQueryLog());

    expect($resolved->relationLoaded('module'))->toBeTrue();

    DB::flushQueryLog();
    // Accessing the already-loaded relation must not add another query.
    $resolved->module->name;
    expect(count(DB::getQueryLog()))->toBe(0);
});

it('keeps a constant query count for the relation picker candidate search regardless of candidate table size', function (): void {
    $subModule = SubModule::factory()->create();

    $mountModal = function () use ($subModule) {
        return Livewire::test(RelationPickerModal::class, [
            'recordViewKey' => 'general.sub-module',
            'recordId' => $subModule->id,
            'section' => 'other-data',
            'tab' => 'applications',
            'contentKey' => 'applications-table',
            'tableClass' => ApplicationsTable::class,
        ])->call('openPicker');
    };

    Application::factory()->count(5)->create(['color' => 'sky']);
    DB::enableQueryLog();
    $mountModal();
    $smallCount = count(DB::getQueryLog());

    Application::factory()->count(200)->create(['color' => 'sky']);
    DB::flushQueryLog();
    $mountModal();
    $largeCount = count(DB::getQueryLog());

    expect($largeCount)->toBe($smallCount);
});
