<?php

namespace App\Support\Code;

use Illuminate\Support\Str;

/**
 * Shared builder for the hierarchical business `code` every ERP navigation
 * entity/business record carries (see .ai/rules/code-field-hierarchy.md).
 * Every model that generates its own record code must go through this
 * service rather than hand-concatenating strings.
 */
class RecordCodeBuilder
{
    /** Application record: "{applicationCode}-{recordSlug}". */
    public function applicationRecordCode(string $applicationCode, string $recordSlug): string
    {
        return $applicationCode.'-'.$recordSlug;
    }

    /** SubApplication record: "{parentRecordCode}-{subApplicationSlug}-{recordSlug}". */
    public function subApplicationRecordCode(string $parentRecordCode, string $subApplicationSlug, string $recordSlug): string
    {
        return $parentRecordCode.'-'.$subApplicationSlug.'-'.$recordSlug;
    }

    /**
     * Kebab-case slug from $base, unique per $exists — appends -2, -3, ...
     * on collision. Falls back to a random suffix if $base slugifies empty.
     *
     * @param  callable(string): bool  $exists
     */
    public function uniqueSlug(string $base, callable $exists): string
    {
        $slug = Str::slug($base);

        if ($slug === '') {
            $slug = Str::lower(Str::random(8));
        }

        $candidate = $slug;
        $suffix = 2;

        while ($exists($candidate)) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
