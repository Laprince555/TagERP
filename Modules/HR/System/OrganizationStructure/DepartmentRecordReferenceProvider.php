<?php

namespace Modules\HR\System\OrganizationStructure;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\OrganizationStructure\Department;

/**
 * Vertical-slice provider for the "hr-org-dep" Application. Owns every
 * record-reference concern for Department: title, route, facts, and scoping.
 */
class DepartmentRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'hr-org-dep';
    }

    public function modelClass(): string
    {
        return Department::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['depth'];
    }

    public function previewColumns(): array
    {
        return ['depth'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('hr.organization-structure.departments.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_values(array_filter([
            new RecordFact('Depth', (string) $record->depth, 10),
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
        /** @var Department $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
