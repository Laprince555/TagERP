<?php

namespace Modules\General\System\World;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Nnjeim\World\Models\Country;

/**
 * Vertical-slice provider for the "gen-wld-ctr" Application. Owns every
 * record-reference concern for Country: title, route, facts, and scoping.
 */
class CountryRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'gen-wld-ctr';
    }

    public function modelClass(): string
    {
        return Country::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'iso2', 'status'];
    }

    public function cardColumns(): array
    {
        return ['region', 'subregion', 'phone_code'];
    }

    public function previewColumns(): array
    {
        return ['region', 'subregion', 'phone_code', 'iso3'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('general.world.countries.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_values(array_filter([
            $record->region ? new RecordFact(__('messages.record_reference.facts.region'), $record->region, 10) : null,
            $record->subregion ? new RecordFact(__('messages.record_reference.facts.subregion'), $record->subregion, 9) : null,
            $record->phone_code ? new RecordFact(__('messages.record_reference.facts.phone_code'), $record->phone_code, 8) : null,
        ]));
    }

    public function previewFacts(Model $record): array
    {
        return array_merge($this->cardFacts($record), array_values(array_filter([
            $record->iso3 ? new RecordFact(__('messages.record_reference.facts.iso3'), $record->iso3, 1) : null,
        ])));
    }

    public function scopeQuery(Builder $query): Builder
    {
        // Mirrors authorize(): only active (status = 1) countries are ever
        // referenceable, so the eager-load/index query excludes inactive
        // rows up front instead of relying solely on the post-load check.
        return $query->where('status', 1);
    }

    /**
     * Pure/query-free: record-level rule only (Application activity and
     * permission_name are enforced once per Application by
     * RecordReferenceAccess, not here). Only active (status = 1) Countries
     * are referenceable/showable.
     */
    public function authorize(Model $record): bool
    {
        /** @var Country $record */
        return (int) $record->status === 1;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
