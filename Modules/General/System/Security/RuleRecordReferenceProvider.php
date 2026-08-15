<?php

namespace Modules\General\System\Security;

use App\Models\Role;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Vertical-slice provider for the "gen-sec-rul" Application. Owns every
 * record-reference concern for Rule (Role): title, route, facts, scoping.
 */
class RuleRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'gen-sec-rul';
    }

    public function modelClass(): string
    {
        return Role::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name'];
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
        return route('general.security.rules.show', ['recordId' => $record->getKey()]);
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
        return $query;
    }

    public function authorize(Model $record): bool
    {
        return true;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
