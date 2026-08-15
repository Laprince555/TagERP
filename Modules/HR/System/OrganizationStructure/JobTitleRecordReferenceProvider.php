<?php

namespace Modules\HR\System\OrganizationStructure;

use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\OrganizationStructure\JobTitle;

/**
 * Vertical-slice provider for the "hr-org-jbt" Application. Owns every
 * record-reference concern for JobTitle: title, route, facts, and scoping.
 */
class JobTitleRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'hr-org-jbt';
    }

    public function modelClass(): string
    {
        return JobTitle::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active', 'department_id'];
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
        return route('hr.organization-structure.job-titles.show', ['recordId' => $record->getKey()]);
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
        /** @var JobTitle $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
