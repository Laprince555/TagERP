<?php

namespace App\Support\DynamicTable\Core\Columns;

use App\Support\DynamicTable\Core\Column;
use App\Support\RecordReference\RecordReferenceRegistry;
use App\Support\RecordReference\RecordReferenceVariant;
use InvalidArgumentException;

/**
 * Renders a record reference (card/tag/icon) instead of raw text.
 * `field()`/`sortable()`/`searchable()` still back real scalar sort/search
 * columns — this column never invents a virtual field.
 *
 * The referenced row is either the table row itself (default — e.g. a
 * Countries table referencing each country) or an already-eager-loaded
 * belongsTo relation (relation()) — never a query built in the renderer.
 */
class RecordReferenceColumn extends Column
{
    protected ?string $applicationCode = null;

    protected RecordReferenceVariant $variant = RecordReferenceVariant::Tag;

    protected ?string $relationPath = null;

    public function applicationCode(string $code): static
    {
        $this->applicationCode = $code;

        return $this;
    }

    public function variant(RecordReferenceVariant $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    /** Dotted belongsTo relation path already declared elsewhere for eager loading, e.g. "country". */
    public function relation(string $path): static
    {
        $this->relationPath = $path;

        return $this;
    }

    public function getApplicationCode(): string
    {
        return $this->applicationCode ?? '';
    }

    public function getVariant(): RecordReferenceVariant
    {
        return $this->variant;
    }

    public function getRelationPath(): ?string
    {
        return $this->relationPath;
    }

    public function getRecord(mixed $row): mixed
    {
        return $this->relationPath ? data_get($row, $this->relationPath) : $row;
    }

    public function validate(): void
    {
        if ($this->applicationCode === null || trim($this->applicationCode) === '') {
            throw new InvalidArgumentException("Record reference column [{$this->key}] must declare applicationCode().");
        }

        if (app(RecordReferenceRegistry::class)->resolve($this->applicationCode) === null) {
            throw new InvalidArgumentException("Record reference column [{$this->key}] uses unknown application code [{$this->applicationCode}].");
        }

        if ($this->relationPath !== null && str_contains($this->relationPath, '.')) {
            throw new InvalidArgumentException("Record reference column [{$this->key}] only supports first-level belongsTo relation paths.");
        }
    }
}
