<?php

use App\Jobs\ExportTableJob;
use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class BulkDeleteTestPolicy
{
    public static int|string|null $protectedId = null;

    public function delete(User $user, User $row): bool
    {
        return $row->getKey() !== self::$protectedId;
    }
}

class BulkActionTestTable extends Table
{
    protected string $tableKey = 'bulk-action-test';

    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [TextColumn::make('name')];
    }

    protected function filters(): array
    {
        return [];
    }

    public function canBulkDelete(): bool
    {
        return true;
    }
}

class ExportExcludesColumnTestTable extends Table
{
    protected string $tableKey = 'export-excludes-column-test';

    protected ?string $model = User::class;

    protected function columns(): array
    {
        return [
            TextColumn::make('name'),
            TextColumn::make('email')->exportable(false),
        ];
    }

    protected function filters(): array
    {
        return [];
    }
}

test('row selection and clear selection work correctly', function () {
    $users = User::factory()->count(3)->create();

    Livewire::test(BulkActionTestTable::class)
        ->call('selectRow', $users[0]->id)
        ->assertSet('selectedIds', [(string) $users[0]->id])
        ->call('selectPage', [$users[1]->id, $users[2]->id])
        ->assertSet('selectedIds', [(string) $users[0]->id, (string) $users[1]->id, (string) $users[2]->id])
        ->call('clearSelection')
        ->assertSet('selectedIds', [])
        ->assertSet('selectAllMatching', false);
});

test('bulk delete deletes selected records', function () {
    $users = User::factory()->count(5)->create();
    $deleteIds = [$users[0]->id, $users[1]->id];

    Livewire::test(BulkActionTestTable::class)
        ->call('selectPage', $deleteIds)
        ->call('bulkDelete');

    expect(User::whereIn('id', $deleteIds)->count())->toBe(0)
        ->and(User::count())->toBe(3);
});

test('bulk delete with selectAllMatching deletes all matching records', function () {
    User::factory()->count(5)->create();

    Livewire::test(BulkActionTestTable::class)
        ->call('toggleSelectAllMatching')
        ->call('bulkDelete');

    expect(User::count())->toBe(0);
});

test('bulk delete is refused when the table has not opted in', function () {
    $users = User::factory()->count(3)->create();

    Livewire::test(ExportExcludesColumnTestTable::class)
        ->call('selectPage', [$users[0]->id, $users[1]->id])
        ->call('bulkDelete')
        ->assertStatus(403);

    expect(User::count())->toBe(3);
});

test('bulk delete skips rows the policy denies', function () {
    $actor = User::factory()->create();
    $users = User::factory()->count(3)->create();

    Gate::policy(User::class, BulkDeleteTestPolicy::class);
    BulkDeleteTestPolicy::$protectedId = $users[0]->id;

    Livewire::actingAs($actor)
        ->test(BulkActionTestTable::class)
        ->call('selectPage', $users->pluck('id')->all())
        ->call('bulkDelete');

    expect(User::whereIn('id', $users->pluck('id'))->pluck('id')->all())
        ->toBe([$users[0]->id]);
});

test('export queues a job for the acting user instead of streaming', function () {
    Queue::fake();

    $actor = User::factory()->create();
    $users = User::factory()->count(3)->create();

    Livewire::actingAs($actor)
        ->test(BulkActionTestTable::class)
        ->call('selectPage', [$users[0]->id])
        ->call('export')
        ->assertStatus(200);

    Queue::assertPushed(ExportTableJob::class, function (ExportTableJob $job) use ($actor, $users) {
        return $job->tableClass === BulkActionTestTable::class
            && $job->userId === $actor->id
            && $job->selectedIds === [(string) $users[0]->id];
    });
});

test('the queued export writes a csv, skips exportable(false), and notifies the user', function () {
    Storage::fake('local');

    $actor = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    (new ExportTableJob(ExportExcludesColumnTestTable::class, [], [], [], $actor->id))->handle();

    $notification = $actor->fresh()->notifications()->sole();
    $csv = Storage::disk('local')->get($notification->data['path']);

    expect($csv)->toContain('Name')
        ->toContain('Jane Doe')
        ->not->toContain('jane@example.com')
        ->and($notification->data['type'])->toBe('export');
});

test('an export download is refused to anyone but its owner', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    (new ExportTableJob(ExportExcludesColumnTestTable::class, [], [], [], $owner->id))->handle();
    $notification = $owner->fresh()->notifications()->sole();

    $this->actingAs($stranger)->get(route('exports.download', $notification->id))->assertNotFound();
    $this->actingAs($owner)->get(route('exports.download', $notification->id))->assertOk();
});
