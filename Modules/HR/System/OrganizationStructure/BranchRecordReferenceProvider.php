<?php

namespace Modules\HR\System\OrganizationStructure;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\OrganizationStructure\Branch;

/**
 * Vertical-slice provider for the "hr-org-brn" Application. Owns every
 * record-reference concern for Branch: title, route, facts, and scoping.
 */
class BranchRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'hr-org-brn';
    }

    public function modelClass(): string
    {
        return Branch::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['phone', 'is_main'];
    }

    public function previewColumns(): array
    {
        return ['phone', 'address', 'is_main'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('hr.organization-structure.branches.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_values(array_filter([
            $record->is_main ? new RecordFact('Main Branch', 'Yes', 10) : null,
            $record->phone ? new RecordFact('Phone', $record->phone, 9) : null,
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
        /** @var Branch $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
