<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cached `id => label` option lists for the active rows of a reference table
 * (banks, categories, currencies, ...) — the source for SelectField dropdowns
 * across DynamicForm definitions. Invalidated by whatever writes to that
 * table, via forget().
 */
class LookupOptions
{
    private const TTL = 3600;

    /** @return array<int, string> */
    public static function active(string $table, string $labelColumn, string $keyColumn = 'id'): array
    {
        return Cache::remember(
            self::cacheKey($table, $labelColumn),
            self::TTL,
            fn (): array => DB::table($table)
                ->where('is_active', true)
                ->pluck($labelColumn, $keyColumn)
                ->all(),
        );
    }

    public static function forget(string $table, string $labelColumn): void
    {
        Cache::forget(self::cacheKey($table, $labelColumn));
    }

    private static function cacheKey(string $table, string $labelColumn): string
    {
        return "lookup-options.{$table}.{$labelColumn}";
    }
}
