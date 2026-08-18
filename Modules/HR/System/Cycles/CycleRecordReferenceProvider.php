<?php

namespace Modules\HR\System\Cycles;

use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\Cycles\Cycle;

/**
 * Vertical-slice provider for the "hr-cyc-cyc" Application.
 */
class CycleRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return Cycle::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return Cycle::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active', 'subject_model', 'document_type_value'];
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
        return route('hr.cycles.cycles.show', ['recordId' => $record->getKey()]);
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
        /** @var Cycle $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
