<?php

namespace App\Support\RecordReference;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One provider per Application. Owns everything needed to present that
 * Application's records as a reference: title, route, facts, query scoping,
 * and authorization. Nothing outside the provider may declare these.
 */
interface RecordReferenceProvider
{
    /** Immutable Application code this provider serves, e.g. "gen-wld-ctr". */
    public function applicationCode(): string;

    /** @return class-string<Model> */
    public function modelClass(): string;

    /**
     * Columns needed to render identity (icon/tag variants + link + title).
     *
     * @return string[]
     */
    public function identityColumns(): array;

    /**
     * Additional columns/relations needed for the card variant beyond identity.
     *
     * @return string[]
     */
    public function cardColumns(): array;

    /**
     * Preview-only columns/relations, fetched solely by the shared preview host.
     *
     * @return string[]
     */
    public function previewColumns(): array;

    public function title(Model $record): string;

    /** Server-generated named record URL, or null if the record cannot be linked. */
    public function url(Model $record): ?string;

    /** 2-4 prioritized facts, immediately visible in the card variant. */
    public function cardFacts(Model $record): array;

    /** Facts shown once the tag/icon preview is opened (may include card facts). */
    public function previewFacts(Model $record): array;

    /**
     * Applies tenant/parent/active/soft-delete scoping. Must not expose a
     * record the current actor cannot see, including through existence.
     */
    public function scopeQuery(Builder $query): Builder;

    public function authorize(Model $record): bool;

    /**
     * Cross-request cache TTL in seconds for preview facts, or null to
     * disable cross-request caching (the default/safe behavior).
     */
    public function cacheTtl(): ?int;
}
