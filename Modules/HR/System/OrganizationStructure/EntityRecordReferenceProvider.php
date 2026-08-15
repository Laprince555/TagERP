<?php

namespace Modules\HR\System\OrganizationStructure;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * Vertical-slice provider for the "hr-org-ent" Application. Owns every
 * record-reference concern for Entity: title, route, facts, and scoping.
 */
class EntityRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'hr-org-ent';
    }

    public function modelClass(): string
    {
        return Entity::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['legal_form', 'is_holding'];
    }

    public function previewColumns(): array
    {
        return ['legal_form', 'tax_authority', 'is_holding'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('hr.organization-structure.entities.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_values(array_filter([
            $record->legal_form ? new RecordFact('Legal Form', $record->legal_form, 10) : null,
            $record->is_holding ? new RecordFact('Holding', 'Yes', 9) : null,
        ]));
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function authorize(Model $record): bool
    {
        /** @var Entity $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
