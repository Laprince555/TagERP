<?php

use App\Models\User;
use App\Support\Organization\OrganizationScope;
use App\Support\Organization\OrganizationScopeResolver;
use App\Support\Organization\OrganizationVersion;
use Modules\HR\Models\OrganizationStructure\Branch;
use Modules\HR\Models\OrganizationStructure\Entity;

test('a cached scope goes stale the instant the org tree changes, not at next login', function () {
    $tree = makeOrgTree();

    $manager = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity_tree',
        'department_scope' => 'all',
    ]);

    $resolver = app(OrganizationScopeResolver::class);

    $before = $resolver->resolve($manager->user);
    expect($before->entityIds)->not->toContain($tree['c']->id + 1000); // sanity: not yet containing a not-yet-created entity id

    // A brand-new entity is grafted under A — Entity's saved() hook bumps
    // OrganizationVersion, so this must appear on the very next resolve(),
    // with no logout/login and no manual cache clear.
    $newChild = Entity::factory()->childOf($tree['a'])->create(['name' => 'Sub D']);

    $after = $resolver->resolve($manager->user);

    expect($after->entityIds)->toContain($newChild->id)
        ->and($before->entityIds)->not->toContain($newChild->id);
});

test('one bump invalidates every cached user scope at once, not just the actor who triggered it', function () {
    $tree = makeOrgTree();

    $managerA = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity_tree',
        'department_scope' => 'all',
    ]);

    $managerB = makeEmployeeUser([
        'entity_id' => $tree['b']->id,
        'branch_id' => Branch::where('entity_id', $tree['b']->id)->first()->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity',
        'department_scope' => 'all',
    ]);

    $resolver = app(OrganizationScopeResolver::class);
    $resolver->resolve($managerA->user);
    $resolver->resolve($managerB->user);

    $versionBefore = app(OrganizationVersion::class)->current();

    // An unrelated write (a third branch, nothing to do with either
    // manager) still bumps the single global counter both cache keys embed.
    Branch::factory()->for($tree['c'])->create(['name' => 'C Secondary']);

    $versionAfter = app(OrganizationVersion::class)->current();

    expect($versionAfter)->toBeGreaterThan($versionBefore);
});

test('reads with no intervening write keep serving the same cache key (no thrash)', function () {
    $tree = makeOrgTree();

    $manager = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity_tree',
        'department_scope' => 'all',
    ]);

    $version = app(OrganizationVersion::class);
    $before = $version->current();

    app(OrganizationScopeResolver::class)->resolve($manager->user);
    app(OrganizationScopeResolver::class)->resolve($manager->user);
    app(OrganizationScopeResolver::class)->resolve($manager->user);

    // Guards against an accidental bump on every read, which would silence
    // rememberForever() entirely and make every resolve() a fresh query.
    expect($version->current())->toBe($before);
});

test('a user with no employee record sees a version bump the same as everyone else', function () {
    $tree = makeOrgTree();
    $userWithNoEmployee = User::factory()->create();

    $resolver = app(OrganizationScopeResolver::class);
    $before = $resolver->resolve($userWithNoEmployee);
    expect($before->entityIds)->toBe([]);

    // Even a deny-all cached scope must be invalidated by a tree change —
    // otherwise a user hired right after this moment would stay
    // incorrectly deny-all until an unrelated write happened to occur.
    Entity::factory()->childOf($tree['a'])->create();

    plantEmployee([
        'user_id' => $userWithNoEmployee->id,
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity',
        'department_scope' => 'all',
        'status' => 'active',
    ]);

    $after = $resolver->resolve($userWithNoEmployee->fresh());
    expect($after->entityIds)->toBe([$tree['a']->id]);
});

test('a scope survives a real round-trip through the database cache store, not just the array test driver', function () {
    // The default testing cache driver ('array') never actually serializes
    // anything, so it can't catch config('cache.serializable_classes') =
    // false turning a cached object into __PHP_Incomplete_Class on read —
    // only a store that genuinely serializes (database, file, redis) can.
    config(['cache.default' => 'database']);

    $tree = makeOrgTree();

    $manager = makeEmployeeUser([
        'entity_id' => $tree['a']->id,
        'branch_id' => $tree['aMain']->id,
        'department_id' => $tree['finance']->id,
        'entity_scope' => 'entity_tree',
        'department_scope' => 'all',
    ]);

    $resolver = app(OrganizationScopeResolver::class);
    $resolver->resolve($manager->user); // primes the cache entry

    // A fresh resolver instance forces resolve() to hit the cache read path
    // rather than any in-memory state the first call might have left behind.
    $scope = app(OrganizationScopeResolver::class)->resolve($manager->user);

    expect($scope)->toBeInstanceOf(OrganizationScope::class)
        ->and($scope->entityIds)->toContain($tree['a']->id);
});
