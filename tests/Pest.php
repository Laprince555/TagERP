<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/**
 * A user who may do everything — the super_admin role AppServiceProvider's
 * Gate::before short-circuits on.
 *
 * Use this in any test that exercises a write (create, link, export, import):
 * a bare User::factory() user holds no permission at all, so those paths are
 * now denied, which is the point. A test that is *about* a restricted actor
 * should keep using a bare factory user instead.
 */
function superAdmin(array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    $user->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

    return $user;
}
