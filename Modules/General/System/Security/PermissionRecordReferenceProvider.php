<?php

namespace Modules\General\System\Security;

use App\Models\Permission;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Vertical-slice provider for the "gen-sec-per" Application. Owns every
 * record-reference concern for Permission: title, route, facts, scoping.
 */
class PermissionRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'gen-sec-per';
    }

    public function modelClass(): string
    {
        return Permission::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name'];
    }

    public function cardColumns(): array
    {
        return ['description', 'guard_name'];
    }

    public function previewColumns(): array
    {
        return ['description'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('general.security.permissions.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_filter([
            'Description' => (string) $record->description,
            'Guard' => (string) $record->guard_name,
        ]);
    }

    public function previewFacts(Model $record): array
    {
        return array_filter([
            'Description' => (string) $record->description,
        ]);
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
