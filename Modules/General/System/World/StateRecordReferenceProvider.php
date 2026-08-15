<?php

namespace Modules\General\System\World;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Nnjeim\World\Models\State;

/**
 * Vertical-slice provider for the "gen-wld-sta" Application. Owns every
 * record-reference concern for State: title, route, facts, and scoping.
 */
class StateRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'gen-wld-sta';
    }

    public function modelClass(): string
    {
        return State::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name'];
    }

    public function cardColumns(): array
    {
        return ['country_code'];
    }

    public function previewColumns(): array
    {
        return ['country_code'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('general.world.states.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_values(array_filter([
            $record->country_code ? new RecordFact(__('messages.record_reference.facts.country'), $record->country_code, 10) : null,
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
     * States carry no active/inactive flag — every seeded State is
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
