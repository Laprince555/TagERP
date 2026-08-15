<?php

namespace Modules\General\System\World;

use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\General\Models\World\Timezone;

/**
 * Vertical-slice provider for the "gen-wld-tzn" Application. Owns every
 * record-reference concern for Timezone: title, route, facts, and scoping.
 */
class TimezoneRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'gen-wld-tzn';
    }

    public function modelClass(): string
    {
        return Timezone::class;
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
        return route('general.world.timezones.show', ['recordId' => $record->getKey()]);
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

    /**
     * Timezones carry no active/inactive flag — every seeded Timezone is
     * referenceable once the Application-level check has passed.
     */
    public function authorize(Model $record): bool
    {
        return true;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
