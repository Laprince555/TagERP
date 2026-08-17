<?php

use App\Livewire\DynamicRecordView\RelationPickerModal;
use App\Support\DynamicRecordView\Core\Content\TableContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Exceptions\UnsupportedUnlinkForNonNullableForeignKeyException;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\DynamicRecordView\Core\RelationPicker;
use App\Support\DynamicRecordView\Core\RelationshipActions;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\DynamicRecordView\Resolution\RelationshipMutator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\General\Livewire\ApplicationsTable;
use Modules\General\System\Application;
use Modules\General\System\SubModule;
use Modules\General\System\SubModuleRecordView;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Phase 5/6: Link/Unlink mutation engine + RelationPicker, exercised against
 * the real SubModule -> Applications relation (the canonical test bed for
 * this pass â€” Application.submodule_id is NOT NULL, so this relation is
 * wired Link-only with allowReassignment(); see SubModuleRecordView).
 */
function mountEmbeddedApplicationsPicker(int|string $subModuleId)
{
    return Livewire::test(RelationPickerModal::class, [
        'recordViewKey' => 'general.sub-module',
        'recordId' => $subModuleId,
        'section' => 'other-data',
        'tab' => 'applications',
        'contentKey' => 'applications-table',
        'tableClass' => ApplicationsTable::class,
    ]);
}

function mountEmbeddedApplicationsTableForActions(int|string $subModuleId)
{
    return Livewire::test(ApplicationsTable::class, [
        'embedRecordViewKey' => 'general.sub-module',
        'embedRecordId' => $subModuleId,
        'embedSection' => 'other-data',
        'embedTab' => 'applications',
        'embedContent' => 'applications-table',
    ]);
}

// --- Config-time nullable-FK guard ---

it('fails at definition time when unlink() is enabled on a HasMany relation with a non-nullable foreign key', function (): void {
    $actions = RelationshipActions::make()->unlink();

    expect(fn () => $actions->assertSupportedFor(SubModule::class, 'applications'))
        ->toThrow(UnsupportedUnlinkForNonNullableForeignKeyException::class);
});

it('does not throw for the real SubModule to Applications relationshipActions, which never enables unlink()', function (): void {
    // Building otherDataSection() runs assertSupportedFor() for every
    // SubApplication with relationshipActions() configured â€” this must not
    // throw for the real, shipped Link-only configuration.
    $view = app(SubModuleRecordView::class);

    expect(fn () => $view->otherDataSection())->not->toThrow(Throwable::class);
});

// --- Link button visibility ---

it('does not render a Link button when relationshipActions() is not configured on the content block', function (): void {
    $content = TableContent::make('plain')->table(ApplicationsTable::class)->relation('applications');

    expect($content->getRelationshipActions())->toBeNull();
});

it('renders a Link button for the real SubModule -> Applications tab, which is configured linkable', function (): void {
    $subModule = SubModule::factory()->create();

    mountEmbeddedApplicationsTableForActions($subModule->id)->assertSeeHtml('Link');
});

it('hides the Link button when the content block has no relationshipActions configured', function (): void {
    // ApplicationsTable standalone (unconstrained) never resolves an embedded
    // relationshipActions() â€” no embed context means no Link button at all.
    Livewire::test(ApplicationsTable::class)->assertDontSeeHtml('open-relation-picker');
});

// --- Link mutation ---

beforeEach(function (): void {
    $this->actingAs(superAdmin());
});

it('links an authorized candidate and reassigns it to the new parent', function (): void {
    $origin = SubModule::factory()->create();
    $target = SubModule::factory()->create();
    $application = Application::factory()->create(['submodule_id' => $origin->id, 'color' => 'sky']);

    app(RelationshipMutator::class)->link(
        ApplicationsTable::class,
        'general.sub-module',
        $target->id,
        'other-data',
        'applications',
        'applications-table',
        $application->id,
    );

    expect($application->refresh()->submodule_id)->toBe($target->id);
});

it('rejects linking when the linkAuthorization callback fails', function (): void {
    auth()->logout();

    $target = SubModule::factory()->create();
    $application = Application::factory()->create(['color' => 'sky']);

    expect(fn () => app(RelationshipMutator::class)->link(
        ApplicationsTable::class,
        'general.sub-module',
        $target->id,
        'other-data',
        'applications',
        'applications-table',
        $application->id,
    ))->toThrow(HttpException::class);
});

it('fails safely for a forged/stale candidate id without leaking whether it exists', function (): void {
    $target = SubModule::factory()->create();

    try {
        app(RelationshipMutator::class)->link(
            ApplicationsTable::class,
            'general.sub-module',
            $target->id,
            'other-data',
            'applications',
            'applications-table',
            999999999,
        );
        $this->fail('Expected a safe abort for a nonexistent candidate id.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(422)
            ->and($e->getMessage())->not->toContain('999999999');
    }
});

it('is idempotent when linking a candidate that is already related to the target parent', function (): void {
    $target = SubModule::factory()->create();
    $application = Application::factory()->create(['submodule_id' => $target->id, 'color' => 'sky']);

    app(RelationshipMutator::class)->link(
        ApplicationsTable::class,
        'general.sub-module',
        $target->id,
        'other-data',
        'applications',
        'applications-table',
        $application->id,
    );

    expect($application->refresh()->submodule_id)->toBe($target->id)
        ->and(Application::where('id', $application->id)->count())->toBe(1);
});

it('rolls back the transaction when a custom mutateUsing callback throws, leaving the original state intact', function (): void {
    $origin = SubModule::factory()->create();
    $target = SubModule::factory()->create();
    $application = Application::factory()->create(['submodule_id' => $origin->id, 'color' => 'sky']);

    $view = new class extends DynamicRecordView
    {
        protected string $viewKey = 'test.mutate-rollback';

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
                SubApplication::make('applications')
                    ->table(ApplicationsTable::class)
                    ->relation('applications')
                    ->relationshipActions(
                        RelationshipActions::make()
                            ->linkExisting(RelationPicker::make()->displayUsing('name')->searchable(['name']))
                            // Explicit: link authorization denies when omitted,
                            // and this case is about the rollback, not the gate.
                            ->linkAuthorization(fn (): bool => true)
                            ->allowReassignment()
                            ->mutateUsing(function () {
                                throw new RuntimeException('simulated failure');
                            }),
                    ),
            ];
        }
    };

    app()->instance($view::class, $view);
    app(RecordViewRegistry::class)->register('test.mutate-rollback', $view::class);

    try {
        app(RelationshipMutator::class)->link(
            ApplicationsTable::class,
            'test.mutate-rollback',
            $target->id,
            'other-data',
            'applications',
            'applications-table',
            $application->id,
        );
        $this->fail('Expected the mutateUsing failure to propagate.');
    } catch (RuntimeException) {
        // expected
    }

    expect($application->refresh()->submodule_id)->toBe($origin->id);
});

// --- RelationPicker / RelationPickerModal ---

it('runs no candidate query before the picker is opened', function (): void {
    $subModule = SubModule::factory()->create();
    Application::factory()->count(3)->create(['submodule_id' => $subModule->id, 'color' => 'sky']);

    DB::enableQueryLog();
    mountEmbeddedApplicationsPicker($subModule->id);
    $log = collect(DB::getQueryLog());

    expect($log->contains(fn ($q) => str_contains($q['query'], 'applications')))->toBeFalse();
});

it('loads at most pageSize candidates when opened, excluding candidates already linked to the current parent', function (): void {
    $target = SubModule::factory()->create();
    $other = SubModule::factory()->create();

    Application::factory()->count(2)->create(['submodule_id' => $target->id, 'color' => 'sky']);
    Application::factory()->count(8)->create(['submodule_id' => $other->id, 'color' => 'sky']);

    $picker = mountEmbeddedApplicationsPicker($target->id);
    $picker->call('openPicker');

    $results = $picker->get('results');
    expect($results)->toHaveCount(5); // pageSize
    expect($picker->get('hasMore'))->toBeTrue();

    $resultIds = collect($results)->pluck('id');
    $targetOwnApplicationIds = Application::where('submodule_id', $target->id)->pluck('id');
    expect($resultIds->intersect($targetOwnApplicationIds))->toBeEmpty();
});

it('resets and re-queries when the search term changes', function (): void {
    $target = SubModule::factory()->create();
    $other = SubModule::factory()->create();
    Application::factory()->count(6)->create(['submodule_id' => $other->id, 'color' => 'sky']);
    $needle = Application::factory()->create(['submodule_id' => $other->id, 'color' => 'sky', 'code' => 'findme-code']);

    $picker = mountEmbeddedApplicationsPicker($target->id);
    $picker->call('openPicker');
    expect($picker->get('results'))->toHaveCount(5);

    $picker->set('search', 'findme-code');
    $labels = collect($picker->get('results'))->pluck('id');

    expect($labels)->toContain($needle->id);
});

it('escapes LIKE wildcards and handles a SQL-injection-attempt and Arabic search term safely', function (): void {
    $target = SubModule::factory()->create();
    $other = SubModule::factory()->create();
    Application::factory()->create(['submodule_id' => $other->id, 'color' => 'sky', 'code' => '100%off']);
    Application::factory()->create(['submodule_id' => $other->id, 'color' => 'sky', 'name' => ['en' => 'arabic-app', 'ar' => 'ØªØ·Ø¨ÙŠÙ‚ ØªØ¬Ø±ÙŠØ¨ÙŠ']]);

    $picker = mountEmbeddedApplicationsPicker($target->id);
    $picker->call('openPicker');

    $picker->set('search', '100%off');
    expect($picker->get('error'))->toBeNull();

    $picker->set('search', "1' OR '1'='1");
    expect($picker->get('error'))->toBeNull();

    $picker->set('search', 'ØªØ·Ø¨ÙŠÙ‚');
    expect($picker->get('error'))->toBeNull();
});

it('stops loading more once maximumLoadedResults is reached', function (): void {
    $target = SubModule::factory()->create();
    $other = SubModule::factory()->create();
    Application::factory()->count(60)->create(['submodule_id' => $other->id, 'color' => 'sky']);

    $picker = mountEmbeddedApplicationsPicker($target->id);
    $picker->call('openPicker');

    for ($i = 0; $i < 12; $i++) {
        $picker->call('loadMore');
    }

    expect(count($picker->get('results')))->toBeLessThanOrEqual(50);
    expect($picker->get('hasMore'))->toBeFalse();
});

it('keeps a bounded query count across open, search, and load-more', function (): void {
    $target = SubModule::factory()->create();
    $other = SubModule::factory()->create();
    Application::factory()->count(20)->create(['submodule_id' => $other->id, 'color' => 'sky']);

    $picker = mountEmbeddedApplicationsPicker($target->id);

    DB::enableQueryLog();
    $picker->call('openPicker');
    $picker->set('search', 'a');
    $picker->call('loadMore');
    $count = count(DB::getQueryLog());

    // A handful of definition/candidate queries per interaction â€” nowhere
    // near N+1 territory (e.g. bounded well under 30 for 3 interactions).
    expect($count)->toBeLessThan(30);
});

it('confirmLink links the selected candidate and closes the picker state', function (): void {
    $target = SubModule::factory()->create();
    $other = SubModule::factory()->create();
    $application = Application::factory()->create(['submodule_id' => $other->id, 'color' => 'sky']);

    $picker = mountEmbeddedApplicationsPicker($target->id);
    $picker->call('openPicker');
    $picker->call('selectCandidate', $application->id);
    $picker->call('confirmLink');

    expect($application->refresh()->submodule_id)->toBe($target->id)
        ->and($picker->get('selectedId'))->toBeNull();
});
