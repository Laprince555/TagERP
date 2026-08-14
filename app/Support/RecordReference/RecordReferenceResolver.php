<?php

namespace App\Support\RecordReference;

use Illuminate\Database\Eloquent\Model;
use Modules\General\System\Application;

/**
 * Builds the identity/card DTOs for an already-loaded record + provider.
 * Never queries — Application metadata is memoized per request by the
 * caller (see RecordReferenceColumn), record data is whatever the caller
 * already fetched.
 */
class RecordReferenceResolver
{
    /**
     * Pure/query-free: $applicationAccessible must already be decided once
     * per Application, outside any per-row loop (see RecordReferenceAccess).
     * Only the provider's pure record-level authorize() runs per row.
     */
    public function identity(RecordReferenceProvider $provider, Application $application, Model $record, bool $applicationAccessible): RecordReferenceIdentity
    {
        $authorized = $applicationAccessible && $provider->authorize($record);

        return new RecordReferenceIdentity(
            applicationCode: $provider->applicationCode(),
            applicationName: (string) $application->name,
            applicationColor: $application->color,
            applicationIcon: $application->icon,
            recordKey: $record->getKey(),
            authorized: $authorized,
            title: $authorized ? $provider->title($record) : null,
            url: $authorized ? $provider->url($record) : null,
        );
    }

    /**
     * @return RecordFact[]
     */
    public function cardFacts(RecordReferenceProvider $provider, Model $record): array
    {
        return $provider->cardFacts($record);
    }
}
