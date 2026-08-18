<?php

namespace Modules\Finance\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shared repeating-rows behaviour for multi-row entry screens (journal
 * lines, AP invoice lines, AP invoice tax/deduction lines): a plain array of
 * rows the browser edits freely, kept as arrays rather than models so a
 * half-typed row costs nothing until Save is pressed.
 *
 * Consumers own their own row shape, validation, and totals — this trait
 * only owns add/remove and the save-time diff against a HasMany relation.
 */
trait HasLineRows
{
    /**
     * One entry per row.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $rows = [];

    /**
     * @param  array<string, mixed>  $defaults
     */
    public function addRow(array $defaults = []): void
    {
        $this->rows[] = $defaults;
    }

    /**
     * @param  array<string, mixed>  $refillDefaults  re-added when removing the last row leaves the grid empty
     */
    public function removeRow(int $index, array $refillDefaults = []): void
    {
        unset($this->rows[$index]);

        $this->rows = array_values($this->rows);

        if ($this->rows === [] && $refillDefaults !== []) {
            $this->addRow($refillDefaults);
        }
    }

    /**
     * Upserts every non-blank row against $relation keyed by its `id` entry,
     * then deletes any related record whose id was not kept — the same
     * diff every line-entry screen needs at save time.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): bool  $isBlank
     * @param  callable(array<string, mixed>): array<string, mixed>  $attributesFor
     * @return list<int> kept record ids, in row order
     */
    protected function syncLineRows(HasMany $relation, array $rows, callable $isBlank, callable $attributesFor): array
    {
        $keptIds = [];

        foreach ($rows as $row) {
            if ($isBlank($row)) {
                continue;
            }

            /** @var Model $line */
            $line = $relation->updateOrCreate(['id' => $row['id'] ?? null], $attributesFor($row));

            $keptIds[] = $line->getKey();
        }

        $relation->whereNotIn($relation->getRelated()->getKeyName(), $keptIds ?: [0])->delete();

        return $keptIds;
    }
}
