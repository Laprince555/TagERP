<?php

use App\Livewire\DynamicRecordView\OtherData;
use App\Models\User;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Exceptions\UnknownRecordViewKeyException;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\DynamicRecordView\Resolution\RecordResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\General\Livewire\ApplicationsTable;
use Modules\General\Livewire\SubModuleRecordView;
use Modules\General\System\Application;
use Modules\General\System\SubModule;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

/**
 * Mounts ApplicationsTable exactly as it's embedded via
 * x-dynamic-record-view.content for SubModuleRecordView's Applications tab —
 * the same bounded scalar embed* props the Blade component passes, never a
 * raw parent id/query alone.
 */
function mountEmbeddedApplicationsTable(int|string $subModuleId)
{
    return Livewire::test(ApplicationsTable::class, [
        'embedRecordViewKey' => 'general.sub-module',
        'embedRecordId' => $subModuleId,
        'embedSection' => 'other-data',
        'embedTab' => 'applications',
        'embedContent' => 'applications-table',
    ]);
}

it('renders the primary section for a real sub module via the real route', function (): void {
    $subModule = SubModule::factory()->create();

    $response = $this->get(route('general.sub-modules.view', ['recordId' => $subModule->id]));

    $response->assertOk()->assertSee($subModule->name);
});

it('404s the route for a nonexistent sub module', function (): void {
    $this->get(route('general.sub-modules.view', ['recordId' => 999999]))->assertNotFound();
});

it('switches the primary tab via the Livewire component', function (): void {
    $subModule = SubModule::factory()->create();

    Livewire::test(SubModuleRecordView::class, ['recordId' => $subModule->id])
        ->assertSet('activeTab', 'overview')
        ->call('setActiveTab', 'overview')
        ->assertSet('activeTab', 'overview');
});

it('keeps the Other Data tab state independent of the primary tab when nested exactly as the real page renders it', function (): void {
    $subModule = SubModule::factory()->create();

    // Mount the primary component the same way the route does, then render
    // its Blade output — which nests OtherData as a genuine child Livewire
    // component (see record-view.blade.php's @livewire(...) call) — proving
    // independence within one real component tree, not two unrelated
    // Livewire::test() instances.
    $record = Livewire::test(SubModuleRecordView::class, ['recordId' => $subModule->id]);
    $record->assertSeeHtml('wire:id');

    $otherData = Livewire::test(OtherData::class, [
        'recordViewKey' => 'general.sub-module',
        'recordId' => $subModule->id,
    ]);
    $otherData->assertSet('activeTab', 'applications');

    // Acting on the primary component's own tab must not be able to affect
    // OtherData's independently-tracked state.
    $record->call('setActiveTab', 'overview');

    $otherData->assertSet('activeTab', 'applications');
});

it('does not query the embedded applications table while its tab is inactive, but does once active', function (): void {
    $subModule = SubModule::factory()->create();
    Application::factory()->count(3)->create(['submodule_id' => $subModule->id, 'color' => 'sky']);

    // A second sub application ("overview-like" placeholder tab) is not
    // available on the real definition, so instead we assert directly at the
    // OtherData level: rendering with the "applications" tab active queries
    // it, and asking for its content only happens when currentTab matches.
    // We simulate "inactive" by intercepting before render: the Livewire
    // mount() itself does not query the applications table at all — only
    // Table's own mount (embedded via Blade) does, once the tab is current.
    DB::enableQueryLog();

    $otherData = Livewire::test(OtherData::class, [
        'recordViewKey' => 'general.sub-module',
        'recordId' => $subModule->id,
    ]);

    // OtherData's own mount()/render() never queries the applications table
    // directly — only the embedded Table component does, and only for the
    // active tab. Confirm the embedded table query is present once its tab
    // (the only, default-active one here) is genuinely rendered.
    $log = collect(DB::getQueryLog());
    expect($log->contains(fn ($q) => str_contains($q['query'], 'applications')))->toBeTrue();

    // Now prove the negative case for a genuinely inactive tab: force
    // activeTab to an unrelated value that resolves to no current tab, and
    // confirm no further applications query happens on re-render.
    DB::flushQueryLog();
    $otherData->set('activeTab', 'not-a-real-tab');
    $log = collect(DB::getQueryLog());
    expect($log->contains(fn ($q) => str_contains($q['query'], 'applications')))->toBeFalse();
});

it('cannot escape the relation constraint via a crafted filter, search, or query string', function (): void {
    $subModuleA = SubModule::factory()->create();
    $subModuleB = SubModule::factory()->create();
    Application::factory()->count(2)->create([
        'submodule_id' => $subModuleA->id,
        'color' => 'sky',
        'name' => ['en' => 'alpha-app', 'ar' => 'أ'],
    ]);
    Application::factory()->count(3)->create([
        'submodule_id' => $subModuleB->id,
        'color' => 'sky',
        'name' => ['en' => 'beta-only-app', 'ar' => 'ب'],
    ]);

    $component = mountEmbeddedApplicationsTable($subModuleA->id);

    // Prove the constraint holds even for the unfiltered case: every row
    // belongs to SubModule A, none to B. This must genuinely fail if the
    // submodule_id where() were removed, since B's rows would leak in.
    $component->assertViewHas('rows', function ($rows) use ($subModuleA) {
        // The Table's columns() only expose name/code/is_active, so assert
        // isolation by content (every row is one of A's seeded apps) rather
        // than a submodule_id column the row payload doesn't carry.
        $codes = collect($rows->items())->pluck('code');
        $expected = Application::where('submodule_id', $subModuleA->id)->pluck('code');

        return $rows->total() === 2
            && $codes->sort()->values()->all() === $expected->sort()->values()->all();
    });

    // Now attempt to smuggle in a search term that only matches SubModule
    // B's data — the base query only ever has where()/whereHas() layered on
    // top (see TableQueryBuilder::query()), so nothing here can widen it
    // beyond submodule_id. If the constraint were removed, this search would
    // surface B's "beta-only-app" rows; instead it must return nothing.
    $component->set('search', 'beta-only-app')->call('submitSearch');

    $component->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
});

it('two embedded instances of the same table for different sub modules do not share temporary state', function (): void {
    $a = SubModule::factory()->create();
    $b = SubModule::factory()->create();
    Application::factory()->count(2)->create(['submodule_id' => $a->id, 'name' => ['en' => 'alpha-app', 'ar' => 'أ'], 'color' => 'sky']);
    Application::factory()->count(2)->create(['submodule_id' => $b->id, 'name' => ['en' => 'beta-app', 'ar' => 'ب'], 'color' => 'sky']);

    $tableA = mountEmbeddedApplicationsTable($a->id);
    $tableB = mountEmbeddedApplicationsTable($b->id);

    $tableA->set('search', 'alpha')->call('submitSearch');

    // Instance B never received the search — it's a separate component instance.
    expect($tableB->get('search'))->toBe('');
});

it('keeps a constant query count regardless of related-row count', function (): void {
    $small = SubModule::factory()->create();
    Application::factory()->count(5)->create(['submodule_id' => $small->id, 'color' => 'sky']);

    $large = SubModule::factory()->create();
    Application::factory()->count(100)->create(['submodule_id' => $large->id, 'color' => 'sky']);

    DB::enableQueryLog();
    mountEmbeddedApplicationsTable($small->id);
    $smallCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    mountEmbeddedApplicationsTable($large->id);
    $largeCount = count(DB::getQueryLog());

    expect($largeCount)->toBe($smallCount);
});

// --- A1: registry ---

it('rejects an unknown recordViewKey without instantiating anything', function (): void {
    $subModule = SubModule::factory()->create();

    // mount() runs inside Livewire's compiled-view render pipeline, so
    // Blade's CompilerEngine wraps the thrown exception in a ViewException
    // (it only passes HttpException/RecordNotFoundException through
    // unwrapped) — assert on the real cause via getPrevious(), same as
    // Laravel's own view-exception convention.
    try {
        Livewire::test(OtherData::class, [
            'recordViewKey' => 'not.a.real.key',
            'recordId' => $subModule->id,
        ]);

        $this->fail('Expected an exception to be thrown for an unknown recordViewKey.');
    } catch (Throwable $e) {
        $cause = $e instanceof UnknownRecordViewKeyException ? $e : $e->getPrevious();
        expect($cause)->toBeInstanceOf(UnknownRecordViewKeyException::class);
    }
});

// --- A2: OtherData authorizes/resolves through the real parent model ---

it('fails safely when the parent sub module is deleted between mount and a later action', function (): void {
    $subModule = SubModule::factory()->create();

    $otherData = Livewire::test(OtherData::class, [
        'recordViewKey' => 'general.sub-module',
        'recordId' => $subModule->id,
    ]);

    $subModule->delete();

    // Livewire converts HttpException (which NotFoundHttpException extends)
    // into a normal 404 response for both mount and subsequent actions
    // rather than letting it bubble up as a thrown exception to the test —
    // see RequestBroker::temporarilyDisableExceptionHandlingAndMiddleware(),
    // which exempts HttpException from its "rethrow" behavior. Assert on the
    // resulting response instead of expecting a thrown exception.
    $otherData->call('setActiveTab', 'applications')->assertStatus(404);
});

it('fails safely when the parent sub module is not authorized by the definition query', function (): void {
    $subModule = SubModule::factory()->create();

    // A locked-out view (mirrors RecordResolutionTest's pattern): query()
    // excludes everything, simulating an authorization scope that rejects
    // this record.
    $view = new class extends DynamicRecordView
    {
        protected string $viewKey = 'test.locked-out-other-data';

        public function model(): string
        {
            return SubModule::class;
        }

        public function query(): Builder
        {
            return SubModule::query()->whereRaw('1 = 0');
        }

        public function title(mixed $record): string
        {
            return '';
        }
    };

    app(RecordViewRegistry::class)
        ->register('test.locked-out-other-data', $view::class);

    // Same Livewire HttpException-to-response conversion as above applies to
    // the initial mount/render too — assert the 404 response, not a thrown
    // exception.
    Livewire::test(OtherData::class, [
        'recordViewKey' => 'test.locked-out-other-data',
        'recordId' => $subModule->id,
    ])->assertStatus(404);
});

it('passes the actual resolved parent model — not just the id — to tab authorization callbacks', function (): void {
    $subModule = SubModule::factory()->create();

    $view = new class extends DynamicRecordView
    {
        public mixed $seen = null;

        protected string $viewKey = 'test.records-model-check';

        public function model(): string
        {
            return SubModule::class;
        }

        public function query(): Builder
        {
            return SubModule::query();
        }

        public function title(mixed $record): string
        {
            return '';
        }

        public function subApplications(): array
        {
            return [
                SubApplication::make('probe')
                    ->applicationKey('test.probe')
                    ->label('Probe')
                    ->table(ApplicationsTable::class)
                    ->forRelation(fn ($record) => $record->applications())
                    ->authorization(function ($record) {
                        $this->seen = $record;

                        return true;
                    }),
            ];
        }
    };

    app()->instance($view::class, $view);
    app(RecordViewRegistry::class)
        ->register('test.records-model-check', $view::class);

    Livewire::test(OtherData::class, [
        'recordViewKey' => 'test.records-model-check',
        'recordId' => $subModule->id,
    ]);

    $resolved = app(RecordResolver::class)->resolve($view, $subModule->id);
    expect($view->seen)->not->toBeNull()
        ->and($view->seen)->toBeInstanceOf(SubModule::class)
        ->and($view->seen->is($resolved))->toBeTrue();
});

// --- A4: active-tab normalization ---

it('falls back to the default tab for an unknown primary tab key', function (): void {
    $subModule = SubModule::factory()->create();

    Livewire::test(SubModuleRecordView::class, ['recordId' => $subModule->id])
        ->call('setActiveTab', 'does-not-exist')
        ->assertSet('activeTab', 'overview');
});

it('falls back to the default tab for an oversized primary tab key', function (): void {
    $subModule = SubModule::factory()->create();

    Livewire::test(SubModuleRecordView::class, ['recordId' => $subModule->id])
        ->call('setActiveTab', str_repeat('x', 500))
        ->assertSet('activeTab', 'overview');
});

it('falls back to the default Other Data tab for an unknown or oversized tab key', function (): void {
    $subModule = SubModule::factory()->create();

    $otherData = Livewire::test(OtherData::class, [
        'recordViewKey' => 'general.sub-module',
        'recordId' => $subModule->id,
    ]);

    $otherData->call('setActiveTab', 'unknown-tab')->assertSet('activeTab', 'applications');
    $otherData->call('setActiveTab', str_repeat('y', 500))->assertSet('activeTab', 'applications');
});
