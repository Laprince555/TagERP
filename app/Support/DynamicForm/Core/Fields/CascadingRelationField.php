<?php

namespace App\Support\DynamicForm\Core\Fields;

use App\Support\DynamicForm\Core\CascadingLevel;
use App\Support\DynamicForm\Core\Field;
use InvalidArgumentException;

/**
 * DSL definition for a multi-step picker (Country -> State -> City): each
 * level is locked until the previous one is chosen, and each level's
 * candidates are filtered by the level above it. Only the LAST level's
 * value is actually persisted (see column()) — the earlier levels exist
 * purely to narrow the search, mirroring how City already implies its
 * State and Country through its own belongsTo chain.
 *
 * This is what makes a 150k-row City table pickable at all: no level ever
 * loads more than pageSize() rows, and City is never queried before a
 * State is chosen.
 */
class CascadingRelationField extends Field
{
    /** @var CascadingLevel[] */
    protected array $levels = [];

    protected ?string $column = null;

    public function level(CascadingLevel $level): static
    {
        $this->levels[] = $level;

        return $this;
    }

    /** @return CascadingLevel[] */
    public function getLevels(): array
    {
        return $this->levels;
    }

    public function lastLevel(): ?CascadingLevel
    {
        return $this->levels === [] ? null : $this->levels[array_key_last($this->levels)];
    }

    /** Destination column on the form's model for the last level's picked id. Defaults to "{key}_id". */
    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getColumn(): string
    {
        return $this->column ?? $this->key.'_id';
    }

    /** The level immediately above $levelKey, or null when it is the first. */
    public function parentLevel(string $levelKey): ?CascadingLevel
    {
        $previous = null;

        foreach ($this->levels as $level) {
            if ($level->getKey() === $levelKey) {
                return $previous;
            }

            $previous = $level;
        }

        return null;
    }

    public function levelByKey(string $levelKey): ?CascadingLevel
    {
        foreach ($this->levels as $level) {
            if ($level->getKey() === $levelKey) {
                return $level;
            }
        }

        return null;
    }

    /** Default label chains the level names, e.g. "Country|State|City". */
    public function getLabel(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        return implode('|', $this->displaySegments([]));
    }

    /**
     * One segment per level for the trigger button: the chosen record's
     * name once picked, otherwise the level's own name. So the button reads
     * "Country|State|City" before anything is chosen and becomes
     * "Egypt|Giza|Faisal" as the user works down the levels.
     *
     * @param  array<string, array{id: int|string, label: string}>  $selected  keyed by level key
     * @return array<int, string>
     */
    public function displaySegments(array $selected): array
    {
        return collect($this->levels)
            ->map(fn (CascadingLevel $level): string => $selected[$level->getKey()]['label']
                ?? str($level->getKey())->headline()->toString())
            ->all();
    }

    /** The trigger button's text: the display segments joined by "|". */
    public function displayText(array $selected): string
    {
        return implode('|', $this->displaySegments($selected));
    }

    public function component(): string
    {
        return 'dynamic-form.fields.cascading-relation';
    }

    /**
     * Only the last level is persisted, so it is the only one validated —
     * and it must reference a row that actually exists, so a forged id
     * fails validation rather than the foreign-key constraint.
     */
    public function getRules(): array
    {
        $last = $this->lastLevel();

        if ($last === null) {
            return parent::getRules();
        }

        $model = new ($last->getModel());
        $connection = $model->getConnectionName();

        $table = $connection === null
            ? $model->getTable()
            : $connection.'.'.$model->getTable();

        return [
            ...parent::getRules(),
            'exists:'.$table.','.$model->getKeyName(),
        ];
    }

    public function validate(): void
    {
        if (count($this->levels) < 2) {
            throw new InvalidArgumentException("Cascading field [{$this->key}] needs at least 2 levels.");
        }

        foreach ($this->levels as $index => $level) {
            if ($index === 0) {
                continue;
            }

            if ($level->getDependsOn() === null) {
                throw new InvalidArgumentException("Cascading field [{$this->key}] level [{$level->getKey()}] must declare dependsOn().");
            }
        }
    }
}
