<?php

namespace Modules\General\System\System;

use App\Models\User;
use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Vertical-slice provider for the "gen-sys-usr" Application. Owns every
 * record-reference concern for User: title, route, facts, and scoping.
 */
class UserRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return 'gen-sys-usr';
    }

    public function modelClass(): string
    {
        return User::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'name', 'email'];
    }

    public function cardColumns(): array
    {
        return ['email'];
    }

    public function previewColumns(): array
    {
        return ['email'];
    }

    public function title(Model $record): string
    {
        return (string) $record->name;
    }

    public function url(Model $record): ?string
    {
        return route('general.system.users.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        return array_values(array_filter([
            $record->email ? new RecordFact('Email', $record->email, 10) : null,
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

    public function authorize(Model $record): bool
    {
        return true;
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
