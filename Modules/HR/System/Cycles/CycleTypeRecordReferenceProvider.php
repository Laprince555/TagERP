<?php

namespace Modules\HR\System\Cycles;

use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\Cycles\CycleType;

/**
 * Vertical-slice provider for the "hr-cyc-typ" Application.
 */
class CycleTypeRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return CycleType::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return CycleType::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active', 'application_code'];
    }

    public function cardColumns(): array
    {
        return [];
    }

    public function previewColumns(): array
    {
        return [];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('hr.cycles.cycle-types.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return [];
    }

    public function previewFacts(Model $record): array
    {
        return [];
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function authorize(Model $record): bool
    {
        /** @var CycleType $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
