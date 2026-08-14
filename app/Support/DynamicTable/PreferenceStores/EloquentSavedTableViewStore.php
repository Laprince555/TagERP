<?php

namespace App\Support\DynamicTable\PreferenceStores;

use App\Models\TableView;
use App\Support\DynamicTable\Core\SavedTableViewStore;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Eloquent-backed saved-view storage. Every query is scoped to the given
 * $user's own id — a view id from another user is never readable or writable.
 */
class EloquentSavedTableViewStore implements SavedTableViewStore
{
    /** Bytes — bounds the stored configuration JSON payload so a saved view can't be abused for storage/DoS. */
    protected const MAX_CONFIGURATION_BYTES = 20_000;

    public function all(Authenticatable $user, string $tableKey): array
    {
        return TableView::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('table_key', $tableKey)
            ->orderBy('name')
            ->get()
            ->map(fn (TableView $view) => [
                'id' => $view->id,
                'name' => $view->name,
                'is_default' => $view->is_default,
                'configuration' => $view->configuration,
            ])
            ->all();
    }

    public function create(Authenticatable $user, string $tableKey, string $name, array $configuration): int
    {
        $name = trim(mb_substr($name, 0, 100));

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'A view name is required.']);
        }

        if (strlen(json_encode($configuration) ?: '') > self::MAX_CONFIGURATION_BYTES) {
            throw ValidationException::withMessages(['configuration' => 'This view configuration is too large to save.']);
        }

        $view = TableView::query()->updateOrCreate(
            ['user_id' => $user->getAuthIdentifier(), 'table_key' => $tableKey, 'name' => $name],
            ['configuration' => $configuration, 'schema_version' => 1],
        );

        return $view->id;
    }

    public function delete(Authenticatable $user, string $tableKey, int $viewId): void
    {
        $view = $this->ownedView($user, $tableKey, $viewId);

        if (! $view) {
            return;
        }

        $wasDefault = $view->is_default;
        $view->delete();

        // Deleting a default view falls back to the table's built-in default — no replacement view is auto-created.
        unset($wasDefault);
    }

    public function setDefault(Authenticatable $user, string $tableKey, int $viewId): void
    {
        $view = $this->ownedView($user, $tableKey, $viewId);

        if (! $view) {
            return;
        }

        DB::transaction(function () use ($user, $tableKey, $view) {
            TableView::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->where('table_key', $tableKey)
                ->update(['is_default' => false]);

            $view->update(['is_default' => true]);
        });
    }

    public function find(Authenticatable $user, string $tableKey, int $viewId): ?array
    {
        $view = $this->ownedView($user, $tableKey, $viewId);

        return $view ? [
            'id' => $view->id,
            'name' => $view->name,
            'is_default' => $view->is_default,
            'configuration' => $view->configuration,
        ] : null;
    }

    public function rename(Authenticatable $user, string $tableKey, int $viewId, string $newName): void
    {
        $newName = trim(mb_substr($newName, 0, 100));

        if ($newName === '') {
            throw ValidationException::withMessages(['name' => 'A view name is required.']);
        }

        $view = $this->ownedView($user, $tableKey, $viewId);

        if (! $view) {
            return;
        }

        $view->update(['name' => $newName]);
    }

    public function update(Authenticatable $user, string $tableKey, int $viewId, array $configuration): void
    {
        if (strlen(json_encode($configuration) ?: '') > self::MAX_CONFIGURATION_BYTES) {
            throw ValidationException::withMessages(['configuration' => 'This view configuration is too large to save.']);
        }

        $view = $this->ownedView($user, $tableKey, $viewId);

        if (! $view) {
            return;
        }

        $view->update(['configuration' => $configuration]);
    }

    protected function ownedView(Authenticatable $user, string $tableKey, int $viewId): ?TableView
    {
        return TableView::query()
            ->where('id', $viewId)
            ->where('user_id', $user->getAuthIdentifier())
            ->where('table_key', $tableKey)
            ->first();
    }
}
