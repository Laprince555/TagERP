<?php

namespace Modules\General\System\World;

use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\General\Models\World\Companies\Company;

/**
 * Vertical-slice provider for the "gen-wld-com" Application. Owns every
 * record-reference concern for Company: title, route, facts, and scoping.
 */
class CompanyRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'gen-wld-com';
    }

    public function modelClass(): string
    {
        return Company::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'code', 'is_active'];
    }

    public function cardColumns(): array
    {
        return ['tax_id', 'phone'];
    }

    public function previewColumns(): array
    {
        return ['tax_id', 'commercial_registration', 'phone', 'email'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('general.world.companies.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_values(array_filter([
            $record->tax_id ? new RecordFact('Tax ID', $record->tax_id, 10) : null,
            $record->phone ? new RecordFact('Phone', $record->phone, 9) : null,
        ]));
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        // Mirrors authorize(): only active companies are ever referenceable.
        return $query->where('is_active', true);
    }

    public function authorize(Model $record): bool
    {
        /** @var Company $record */
        return (bool) $record->is_active;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
