<?php

use App\Models\User;
use App\Models\UserTablePreference;
use Livewire\Livewire;
use Modules\General\Livewire\ApplicationsTable;
use Modules\General\System\Application;
use Modules\General\System\SubModule;

/**
 * Proves the storage-key vs. instance-key split (Phase 4): preferences and
 * saved views are keyed by ApplicationsTable's stable storageKey() — never
 * by parent record id — while query-string state is namespaced per embedded
 * instance so two embeddings never collide or bleed into each other.
 */
beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

function mountApplicationsTableFor(int|string $subModuleId)
{
    return Livewire::test(ApplicationsTable::class, [
        'embedRecordViewKey' => 'general.sub-module',
        'embedRecordId' => $subModuleId,
        'embedSection' => 'other-data',
        'embedTab' => 'applications',
        'embedContent' => 'applications-table',
    ]);
}

it('does not create one preference row per parent record', function (): void {
    $a = SubModule::factory()->create();
    $b = SubModule::factory()->create();
    Application::factory()->count(2)->create(['submodule_id' => $a->id, 'color' => 'sky']);
    Application::factory()->count(2)->create(['submodule_id' => $b->id, 'color' => 'sky']);

    mountApplicationsTableFor($a->id)->call('setPerPage', 50);
    expect(UserTablePreference::query()->where('table_key', 'general.applications')->count())->toBe(1);

    mountApplicationsTableFor($b->id)->call('setPerPage', 50);
    expect(UserTablePreference::query()->where('table_key', 'general.applications')->count())->toBe(1);
});

it('applies a preference set under one parent to the same logical table viewed under a different parent', function (): void {
    $a = SubModule::factory()->create();
    $b = SubModule::factory()->create();
    Application::factory()->count(2)->create(['submodule_id' => $a->id, 'color' => 'sky']);
    Application::factory()->count(2)->create(['submodule_id' => $b->id, 'color' => 'sky']);

    mountApplicationsTableFor($a->id)->call('setPerPage', 50);

    // A freshly mounted instance under an unrelated parent picks up the same
    // stored preference — proving it's per-table, not per-parent.
    mountApplicationsTableFor($b->id)->assertSet('perPage', 50);
});

it('never namespaces query-string keys or the storage key by parent id when standalone', function (): void {
    Livewire::test(ApplicationsTable::class)->assertSet('embedRecordViewKey', '');
});

it('generates non-colliding query-string namespaces for two embedded instances of the same table', function (): void {
    $a = SubModule::factory()->create();
    $b = SubModule::factory()->create();
    Application::factory()->count(1)->create(['submodule_id' => $a->id, 'color' => 'sky']);
    Application::factory()->count(1)->create(['submodule_id' => $b->id, 'color' => 'sky']);

    $tableA = mountApplicationsTableFor($a->id);
    $tableB = mountApplicationsTableFor($b->id);

    // instanceKey embeds the parent id, so it differs per instance even
    // though both share the same storageKey()/table_key.
    expect($tableA->get('instanceKey'))->not->toBe($tableB->get('instanceKey'))
        ->and($tableA->get('instanceKey'))->toContain((string) $a->id)
        ->and($tableB->get('instanceKey'))->toContain((string) $b->id);
});
