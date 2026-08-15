<?php

namespace App\Support\Organization;

use Illuminate\Support\Facades\Cache;

/**
 * A single global counter stamped into every cached OrganizationScope key.
 * Bumping it once logically invalidates every user's cached scope at the
 * same instant, instead of hunting down which users a tree edit affected.
 */
class OrganizationVersion
{
    private const CACHE_KEY = 'org_scope_version';

    public function current(): int
    {
        return (int) Cache::rememberForever(self::CACHE_KEY, fn (): int => 1);
    }

    public function bump(): void
    {
        Cache::forever(self::CACHE_KEY, $this->current() + 1);
    }
}
