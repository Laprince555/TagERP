<?php

namespace App\Support\DynamicTable\Core;

/**
 * Immutable DTO for user table preferences: column visibility, order, per-page, density.
 * Not persisted: current page, search text, filter state (those are temporary query params).
 * Normalized via TableDefinition to handle stale/unauthorized columns automatically.
 */
class TablePreferences
{
    /** @var string[] Column keys the user has explicitly hidden (or defaultHidden columns) */
    public readonly array $hiddenColumns;

    /** @var string[] Ordered column keys; must include all authorized toggleable columns */
    public readonly array $columnOrder;

    public readonly int $perPage;

    public readonly string $density;

    public readonly int $schemaVersion;

    /**
     * @param  string[]  $hiddenColumns
     * @param  string[]  $columnOrder
     */
    public function __construct(
        array $hiddenColumns = [],
        array $columnOrder = [],
        int $perPage = TableState::DEFAULT_PER_PAGE,
        string $density = 'comfortable',
        int $schemaVersion = 1,
    ) {
        $this->hiddenColumns = $hiddenColumns;
        $this->columnOrder = $columnOrder;
        $this->perPage = max(TableState::PER_PAGE_OPTIONS[0], min($perPage, max(TableState::PER_PAGE_OPTIONS)));
        $this->density = in_array($density, ['compact', 'comfortable', 'spacious']) ? $density : 'comfortable';
        $this->schemaVersion = $schemaVersion;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'version' => $this->schemaVersion,
            'hidden_columns' => $this->hiddenColumns,
            'column_order' => $this->columnOrder,
            'per_page' => $this->perPage,
            'density' => $this->density,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            hiddenColumns: (array) ($data['hidden_columns'] ?? []),
            columnOrder: (array) ($data['column_order'] ?? []),
            perPage: (int) ($data['per_page'] ?? TableState::DEFAULT_PER_PAGE),
            density: (string) ($data['density'] ?? 'comfortable'),
            schemaVersion: (int) ($data['version'] ?? 1),
        );
    }

    /**
     * Normalize stale preferences against current table definition.
     * Handles added columns, removed columns, renamed columns, unauthorized columns.
     */
    public static function normalize(TableDefinition $definition, ?array $raw): self
    {
        $prefs = $raw ? self::fromArray($raw) : new self(
            hiddenColumns: collect($definition->columns)
                ->filter(fn (Column $col) => $col->isHiddenByDefault())
                ->map(fn (Column $col) => $col->getKey())
                ->values()
                ->all(),
        );

        // Get all currently authorized toggleable column keys
        $authorizedToggleable = collect($definition->columns)
            ->filter(fn (Column $col) => $col->isToggleable() && $col->isVisible())
            ->map(fn (Column $col) => $col->getKey())
            ->values()
            ->all();

        $fixedColumns = collect($definition->columns)
            ->filter(fn (Column $col) => ! $col->isToggleable() && $col->isVisible())
            ->map(fn (Column $col) => $col->getKey())
            ->values()
            ->all();

        // Normalize hidden columns: keep only those that still exist and are authorized
        $hiddenColumns = array_intersect($prefs->hiddenColumns, $authorizedToggleable);

        // Normalize column order: start with fixed columns, then add toggleable in their stored order
        $newOrder = [...$fixedColumns];
        foreach ($prefs->columnOrder as $key) {
            if (in_array($key, $authorizedToggleable) && ! in_array($key, $newOrder)) {
                $newOrder[] = $key;
            }
        }
        // Add any new columns that weren't in stored order
        foreach ($authorizedToggleable as $key) {
            if (! in_array($key, $newOrder)) {
                $newOrder[] = $key;
            }
        }

        return new self(
            hiddenColumns: $hiddenColumns,
            columnOrder: $newOrder,
            perPage: $prefs->perPage,
            density: $prefs->density,
            schemaVersion: 1, // Always bump to current version after normalize
        );
    }

    /**
     * Return column keys that are currently visible (not in hiddenColumns).
     *
     * @return string[]
     */
    public function visibleColumns(): array
    {
        return array_diff($this->columnOrder, $this->hiddenColumns);
    }
}
