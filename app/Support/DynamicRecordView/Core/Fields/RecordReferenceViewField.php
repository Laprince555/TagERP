<?php

namespace App\Support\DynamicRecordView\Core\Fields;

use App\Support\DynamicTable\Core\Exceptions\InvalidModelException;
use App\Support\RecordReference\RecordReferenceRegistry;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordReferenceViewField extends Field
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

    public function validate(Model $model): void
    {
        if ($this->applicationCode === null || trim($this->applicationCode) === '') {
            throw new \InvalidArgumentException("Record reference field [{$this->key}] must declare applicationCode().");
        }

        $provider = app(RecordReferenceRegistry::class)->resolve($this->applicationCode);
        if ($provider === null) {
            throw new \InvalidArgumentException("Record reference field [{$this->key}] uses unknown application code [{$this->applicationCode}].");
        }

        if ($this->relationPath !== null && str_contains($this->relationPath, '.')) {
            throw new \InvalidArgumentException("Record reference field [{$this->key}] only supports first-level belongsTo relation paths.");
        }

        if ($this->relationPath) {
            if (! method_exists($model, $this->relationPath)) {
                throw new \InvalidArgumentException("Record reference field [{$this->key}] relation [{$this->relationPath}] does not exist on model [".get_class($model).'].');
            }
            $relation = $model->{$this->relationPath}();
            if (! $relation instanceof BelongsTo) {
                throw new \InvalidArgumentException("Record reference field [{$this->key}] relation [{$this->relationPath}] must be a BelongsTo relation.");
            }
            $relatedClass = get_class($relation->getRelated());
            if (! is_a($relatedClass, $provider->modelClass(), true)) {
                throw new InvalidModelException("Record reference field [{$this->key}] relation [{$this->relationPath}] points to [{$relatedClass}], expected [{$provider->modelClass()}].");
            }
        } else {
            $modelClass = get_class($model);
            if (! is_a($modelClass, $provider->modelClass(), true)) {
                throw new InvalidModelException("Record reference field [{$this->key}] points to [{$modelClass}], expected [{$provider->modelClass()}].");
            }
        }
    }
}
