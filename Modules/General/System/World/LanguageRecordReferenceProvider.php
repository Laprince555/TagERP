<?php

namespace Modules\General\System\World;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\General\Models\World\Language;

/**
 * Vertical-slice provider for the "gen-wld-lng" Application. Owns every
 * record-reference concern for Language: title, route, facts, and scoping.
 */
class LanguageRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'gen-wld-lng';
    }

    public function modelClass(): string
    {
        return Language::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code'];
    }

    public function cardColumns(): array
    {
        return ['code'];
    }

    public function previewColumns(): array
    {
        return ['code', 'name_native', 'dir'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('general.world.languages.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_values(array_filter([
            $record->code ? new RecordFact(__('messages.record_reference.facts.code'), $record->code, 10) : null,
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
     * Languages carry no active/inactive flag — every seeded Language is
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
