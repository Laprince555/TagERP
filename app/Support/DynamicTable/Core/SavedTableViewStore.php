<?php

namespace App\Support\DynamicTable\Core;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Storage contract for personal saved table views (filters, search, sort,
 * column visibility/order, per-page) — distinct from TablePreferenceStore,
 * which only persists automatic column/per-page preferences. Implementations
 * must enforce that a user can only read/write their own views.
 */
interface SavedTableViewStore
{
    /** @return array<int, array{id: int, name: string, is_default: bool, configuration: array<string, mixed>}> */
    public function all(Authenticatable $user, string $tableKey): array;

    /** @param array<string, mixed> $configuration Raw TableState-shaped array (search/filters/sorts/perPage/visibleColumns/columnOrder) */
    public function create(Authenticatable $user, string $tableKey, string $name, array $configuration): int;

    public function delete(Authenticatable $user, string $tableKey, int $viewId): void;

    public function setDefault(Authenticatable $user, string $tableKey, int $viewId): void;

    /** @return array<string, mixed>|null */
    public function find(Authenticatable $user, string $tableKey, int $viewId): ?array;

    public function rename(Authenticatable $user, string $tableKey, int $viewId, string $newName): void;

    /** @param array<string, mixed> $configuration */
    public function update(Authenticatable $user, string $tableKey, int $viewId, array $configuration): void;
}
