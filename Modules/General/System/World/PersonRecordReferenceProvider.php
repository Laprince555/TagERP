<?php

namespace Modules\General\System\World;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\General\Models\World\People\Person;

/**
 * Vertical-slice provider for the "gen-wld-per" Application. Owns every
 * record-reference concern for Person: title, route, facts, and scoping.
 */
class PersonRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'gen-wld-per';
    }

    public function modelClass(): string
    {
        return Person::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'full_name', 'code'];
    }

    public function cardColumns(): array
    {
        return ['phone', 'email'];
    }

    public function previewColumns(): array
    {
        return ['phone', 'email', 'nickname'];
    }

    public function title(Model $record): string
    {
        return (string) $record->full_name;
    }

    public function url(Model $record): ?string
    {
        return route('general.world.people.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_values(array_filter([
            $record->phone ? new RecordFact('Phone', $record->phone, 10) : null,
            $record->email ? new RecordFact('Email', $record->email, 9) : null,
        ]));
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query;
    }

    /**
     * People carry no active/inactive flag — every non-deleted Person is
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
