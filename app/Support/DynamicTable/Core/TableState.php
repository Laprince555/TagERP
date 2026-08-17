<?php

namespace App\Support\DynamicTable\Core;

use App\Support\DynamicTable\Core\Filters\BelongsToFilter;
use App\Support\DynamicTable\Core\Filters\BooleanFilter;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Filters\NumberFilter;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use Carbon\Carbon;

/**
 * Validated, normalized client state. This is the SECURITY BOUNDARY: raw,
 * untrusted arrays (Livewire public properties, query-string input) must be
 * passed through TableState::normalize() before ever reaching the query
 * engine. Unknown columns/operators are dropped, invalid values are dropped
 * or defaulted, and every collection is size-clamped.
 */
class TableState
{
    /** @var int[] allow-listed per-page values */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 25;

    public const MAX_SEARCH_LENGTH = 200;

    public const MAX_MULTI_SELECT = 50;

    /** Maximum simultaneously active filters — bounds query complexity and payload size. */
    public const MAX_FILTERS = 20;

    /** Maximum simultaneous sort entries. */
    public const MAX_SORTS = 5;

    /** Safe upper bound on the requested page number — beyond this, offset-based pagination cost is not worth allowing. */
    public const MAX_PAGE = 100_000;

    /**
     * @param  array<string, array{operator: string, value: mixed}>  $filters
     * @param  array<int, array{column: string, direction: string}>  $sorts
     * @param  string[]  $visibleColumns
     * @param  string[]  $columnOrder
     */
    protected function __construct(
        public readonly string $search,
        public readonly array $filters,
        public readonly array $sorts,
        public readonly int $page,
        public readonly int $perPage,
        public readonly array $visibleColumns,
        public readonly array $columnOrder,
        public readonly ?string $cursor = null,
    ) {}

    /**
     * The single canonical rendering order: $columnOrder (the user's actual
     * requested/persisted order) filtered down to only currently-visible
     * columns. Every consumer that needs "which columns, in what order" —
     * table headers, cells, the column manager, select/eager-load building,
     * export — must use this, not $visibleColumns alone (whose own order
     * merely reflects authorizedColumns() definition order, not the user's
     * chosen order) and not $columnOrder alone (which includes hidden columns).
     *
     * @return string[]
     */
    public function orderedVisibleColumns(): array
    {
        return array_values(array_intersect($this->columnOrder, $this->visibleColumns));
    }

    /**
     * @param  array<string, mixed>  $raw  Untrusted input, e.g. from Livewire public properties.
     */
    public static function normalize(array $raw, TableDefinition $definition): self
    {
        return new self(
            search: self::normalizeSearch($raw['search'] ?? ''),
            filters: self::normalizeFilters($raw['filters'] ?? [], $definition),
            sorts: self::normalizeSorts($raw['sorts'] ?? [], $definition),
            page: min(self::MAX_PAGE, max(1, (int) ($raw['page'] ?? 1))),
            perPage: self::normalizePerPage($raw['perPage'] ?? self::DEFAULT_PER_PAGE),
            visibleColumns: self::normalizeVisibleColumns($raw['visibleColumns'] ?? null, $definition),
            columnOrder: self::normalizeColumnOrder($raw['columnOrder'] ?? [], $definition),
            cursor: is_string($raw['cursor'] ?? null) && $raw['cursor'] !== '' ? $raw['cursor'] : null,
        );
    }

    protected static function normalizeSearch(mixed $search): string
    {
        if (! is_string($search)) {
            return '';
        }

        return mb_substr(trim($search), 0, self::MAX_SEARCH_LENGTH);
    }

    protected static function normalizePerPage(mixed $perPage): int
    {
        $perPage = (int) $perPage;

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::DEFAULT_PER_PAGE;
    }

    /**
     * @return string[]
     */
    protected static function normalizeVisibleColumns(mixed $visibleColumns, TableDefinition $definition): array
    {
        // Unauthorized (visible() === false) columns are excluded from the allow-list entirely —
        // no client input, however crafted, can surface them.
        $authorized = $definition->authorizedColumns();
        $allKeys = array_keys($authorized);

        if (! is_array($visibleColumns)) {
            // Default: every non-hidden-by-default authorized column.
            return array_values(array_filter($allKeys, fn (string $key): bool => ! $authorized[$key]->isHiddenByDefault()));
        }

        // array_unique first: array_intersect() preserves duplicates from $visibleColumns verbatim,
        // e.g. array_intersect(['a','a'], ['a','b']) === ['a','a'] — a tampered/duplicated client
        // payload must never produce a duplicate column key downstream.
        $visible = array_values(array_intersect($allKeys, array_unique($visibleColumns)));

        // Non-toggleable columns are always visible, regardless of client input.
        foreach ($authorized as $key => $column) {
            if (! $column->isToggleable() && ! in_array($key, $visible, true)) {
                $visible[] = $key;
            }
        }

        return $visible;
    }

    /**
     * @return string[]
     */
    protected static function normalizeColumnOrder(mixed $columnOrder, TableDefinition $definition): array
    {
        $allKeys = array_keys($definition->authorizedColumns());

        if (! is_array($columnOrder)) {
            return $allKeys;
        }

        // First-arg order is preserved by array_intersect(), so keep the client's requested
        // order here (deduped), restricted to authorized keys — unlike visibleColumns above,
        // where order doesn't matter and we intersect the other way.
        $ordered = array_values(array_intersect(array_unique($columnOrder), $allKeys));
        $missing = array_values(array_diff($allKeys, $ordered));

        return [...$ordered, ...$missing];
    }

    /**
     * @return array<int, array{column: string, direction: string}>
     */
    protected static function normalizeSorts(mixed $sorts, TableDefinition $definition): array
    {
        if (! is_array($sorts)) {
            return [];
        }

        $normalized = [];
        $seenColumns = [];
        foreach ($sorts as $sort) {
            if (count($normalized) >= self::MAX_SORTS) {
                break;
            }

            if (! is_array($sort)) {
                continue;
            }

            $column = $sort['column'] ?? null;
            $direction = $sort['direction'] ?? null;

            if (! is_string($column) || ! $definition->column($column)?->isSortable()) {
                continue;
            }

            if (! in_array($direction, ['asc', 'desc'], true)) {
                continue;
            }

            // A column may only appear once in the sort list — the first (highest-priority)
            // occurrence wins, later duplicates for the same column are dropped.
            if (isset($seenColumns[$column])) {
                continue;
            }
            $seenColumns[$column] = true;

            $normalized[] = ['column' => $column, 'direction' => $direction];
        }

        return $normalized;
    }

    /**
     * @return array<string, array{operator: string, value: mixed}>
     */
    protected static function normalizeFilters(mixed $filters, TableDefinition $definition): array
    {
        if (! is_array($filters)) {
            return [];
        }

        $normalized = [];
        foreach ($filters as $key => $entry) {
            if (count($normalized) >= self::MAX_FILTERS) {
                break;
            }

            $filter = is_string($key) ? $definition->filter($key) : null;

            if (! $filter || ! is_array($entry)) {
                continue;
            }

            $result = self::normalizeFilterEntry($filter, $entry);

            if ($result !== null) {
                $normalized[$key] = $result;
            }
        }

        return $normalized;
    }

    /**
     * @param  array{operator?: mixed, value?: mixed}  $entry
     * @return array{operator: string, value: mixed}|null
     */
    protected static function normalizeFilterEntry(Filter $filter, array $entry): ?array
    {
        $operator = $entry['operator'] ?? null;
        $value = $entry['value'] ?? null;

        return match (true) {
            $filter instanceof TextFilter => self::normalizeEnumOperatorFilter($filter->getOperators(), $operator, $value, fn ($v) => mb_substr((string) $v, 0, self::MAX_SEARCH_LENGTH)),
            $filter instanceof NumberFilter => self::normalizeEnumOperatorFilter($filter->getOperators(), $operator, $value, fn ($v) => self::normalizeNumberValue($operator, $v)),
            $filter instanceof DateFilter => self::normalizeDateFilterEntry($filter, $operator, $value),
            $filter instanceof BooleanFilter => self::normalizeBooleanFilterValue($value),
            $filter instanceof EnumFilter => self::normalizeEnumFilterValue($filter, $value),
            $filter instanceof BelongsToFilter => self::normalizeBelongsToValue($filter, $value),
            default => null,
        };
    }

    /**
     * @param  array<int, TextOperator|NumberOperator|DateOperator>  $operators
     */
    protected static function normalizeEnumOperatorFilter(array $operators, mixed $operator, mixed $value, callable $valueNormalizer): ?array
    {
        $operatorValues = array_map(fn ($op) => $op->value, $operators);

        if (! in_array($operator, $operatorValues, true)) {
            return null;
        }

        if (in_array($operator, ['is_empty', 'is_not_empty', 'today', 'yesterday', 'this_week', 'this_month'], true)) {
            return ['operator' => $operator, 'value' => null];
        }

        // An operator with no value is not a filter. Every table seeds a
        // default operator for its text/number/date filters (so an implicit
        // operator is never silently dropped), which means an untouched
        // filter arrives here as e.g. contains + null. Cast to string that
        // became `LIKE '%%'` — which is NULL, not true, for any row whose
        // column is NULL, quietly hiding every unposted journal from a list
        // the user only meant to filter by status.
        if ($value === null || $value === '') {
            return null;
        }

        $normalizedValue = $valueNormalizer($value);

        return $normalizedValue === null ? null : ['operator' => $operator, 'value' => $normalizedValue];
    }

    /**
     * Accepts a real bool (programmatic callers) or the string form an HTML
     * <select>/wire:model submits ('1'/'0'/'true'/'false'). An empty string
     * or null means "no filter" (tri-state "All"), not false.
     */
    protected static function normalizeBooleanFilterValue(mixed $value): ?array
    {
        if (is_bool($value)) {
            return ['operator' => 'equals', 'value' => $value];
        }

        if (in_array($value, ['1', 'true'], true)) {
            return ['operator' => 'equals', 'value' => true];
        }

        if (in_array($value, ['0', 'false'], true)) {
            return ['operator' => 'equals', 'value' => false];
        }

        return null;
    }

    protected static function normalizeNumberValue(?string $operator, mixed $value): array|float|null
    {
        if (in_array($operator, ['between', 'not_between'], true)) {
            if (! is_array($value) || ! isset($value[0], $value[1]) || ! is_numeric($value[0]) || ! is_numeric($value[1])) {
                return null;
            }

            return [(float) $value[0], (float) $value[1]];
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * DateFilter is handled entirely separately from the other operator-based
     * filters (not via normalizeEnumOperatorFilter) because correct day-boundary
     * math is timezone-dependent and can only be done here, in Core, where the
     * filter's declared timezone (DateFilter::getTimezone()) is known. Every
     * date/datetime bound submitted by the client is strictly parsed in that
     * timezone, then converted to config('app.timezone') (the database/app
     * storage timezone) as the FINAL, fully-resolved instant(s) — never a bare
     * calendar date. TableQueryBuilder binds these values directly with no
     * further boundary math, so "today" always means today in the filter's
     * timezone, not the database server's.
     *
     * Strict parsing: Carbon::createFromFormat() with an exact format
     * ('Y-m-d' or 'Y-m-d\TH:i' for ->withTime()), plus a round-trip check
     * (re-formatting the parsed value must reproduce the original string)
     * to reject auto-corrected invalid dates like "2026-02-30".
     */
    protected static function normalizeDateFilterEntry(DateFilter $filter, mixed $operator, mixed $value): ?array
    {
        $operatorValues = array_map(fn ($op) => $op->value, $filter->getOperators());

        if (! in_array($operator, $operatorValues, true)) {
            return null;
        }

        if (in_array($operator, ['is_empty', 'is_not_empty'], true)) {
            return ['operator' => $operator, 'value' => null];
        }

        $timezone = $filter->getTimezone();
        $dbTimezone = config('app.timezone', 'UTC');
        $format = $filter->hasTime() ? 'Y-m-d\TH:i' : 'Y-m-d';

        $parseOne = function (mixed $raw) use ($format, $timezone): ?Carbon {
            if (! is_string($raw) || $raw === '') {
                return null;
            }

            try {
                $parsed = Carbon::createFromFormat($format, $raw, $timezone);
            } catch (\Throwable) {
                return null;
            }

            if ($parsed === false || $parsed->format($format) !== $raw) {
                return null;
            }

            return $parsed;
        };

        // Given an instant in the filter's timezone, resolve [start, end] of its calendar
        // day (or the exact instant twice, if the filter carries time-of-day precision),
        // converted to the database timezone.
        $dayBounds = function (Carbon $localInstant) use ($filter, $dbTimezone): array {
            if ($filter->hasTime()) {
                $converted = $localInstant->copy()->setTimezone($dbTimezone);

                return [$converted, $converted];
            }

            return [
                $localInstant->copy()->startOfDay()->setTimezone($dbTimezone),
                $localInstant->copy()->endOfDay()->setTimezone($dbTimezone),
            ];
        };

        $fmt = fn (Carbon $c): string => $c->format('Y-m-d H:i:s');

        return match ($operator) {
            'today', 'yesterday', 'this_week', 'this_month' => (function () use ($operator, $timezone, $dbTimezone, $fmt) {
                $now = Carbon::now($timezone);
                [$start, $end] = match ($operator) {
                    'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                    'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
                    'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                    'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                };

                return ['operator' => $operator, 'value' => [$fmt($start->setTimezone($dbTimezone)), $fmt($end->setTimezone($dbTimezone))]];
            })(),

            'on' => (function () use ($value, $parseOne, $dayBounds, $fmt, $operator) {
                $d = $parseOne($value);
                if (! $d) {
                    return null;
                }
                [$start, $end] = $dayBounds($d);

                return ['operator' => $operator, 'value' => [$fmt($start), $fmt($end)]];
            })(),

            'before' => (function () use ($value, $parseOne, $dayBounds, $fmt, $operator) {
                $d = $parseOne($value);
                if (! $d) {
                    return null;
                }
                [$start] = $dayBounds($d);

                return ['operator' => $operator, 'value' => $fmt($start)];
            })(),

            'before_or_on' => (function () use ($value, $parseOne, $dayBounds, $fmt, $operator) {
                $d = $parseOne($value);
                if (! $d) {
                    return null;
                }
                [, $end] = $dayBounds($d);

                return ['operator' => $operator, 'value' => $fmt($end)];
            })(),

            'after' => (function () use ($value, $parseOne, $dayBounds, $fmt, $operator) {
                $d = $parseOne($value);
                if (! $d) {
                    return null;
                }
                [, $end] = $dayBounds($d);

                return ['operator' => $operator, 'value' => $fmt($end)];
            })(),

            'after_or_on' => (function () use ($value, $parseOne, $dayBounds, $fmt, $operator) {
                $d = $parseOne($value);
                if (! $d) {
                    return null;
                }
                [$start] = $dayBounds($d);

                return ['operator' => $operator, 'value' => $fmt($start)];
            })(),

            'between', 'not_between' => (function () use ($value, $parseOne, $dayBounds, $fmt, $operator) {
                if (! is_array($value) || ! isset($value[0], $value[1])) {
                    return null;
                }
                $d0 = $parseOne($value[0]);
                $d1 = $parseOne($value[1]);
                if (! $d0 || ! $d1) {
                    return null;
                }
                [$start] = $dayBounds($d0);
                [, $end] = $dayBounds($d1);

                return ['operator' => $operator, 'value' => [$fmt($start), $fmt($end)]];
            })(),

            default => null,
        };
    }

    /**
     * @return array{operator: string, value: mixed}|null
     */
    protected static function normalizeEnumFilterValue(EnumFilter $filter, mixed $value): ?array
    {
        $enumClass = $filter->getEnumClass();
        $validValues = array_map(fn ($case) => $case->value, $enumClass::cases());

        if ($filter->isMultiple()) {
            // A scalar is accepted and wrapped: a saved view stored before
            // this filter became multi-select still carries one bare value,
            // and dropping it would silently un-filter the saved view.
            $value = is_array($value) ? $value : ($value === null || $value === '' ? [] : [$value]);

            $values = array_slice(array_values(array_intersect($value, $validValues)), 0, self::MAX_MULTI_SELECT);

            return $values === [] ? null : ['operator' => 'in', 'value' => $values];
        }

        return in_array($value, $validValues, true) ? ['operator' => 'equals', 'value' => $value] : null;
    }

    /**
     * @return array{operator: string, value: mixed}|null
     */
    /**
     * A related model's primary key isn't always an int — UUID/ULID string
     * keys are common. A numeric value is cast to int; a non-empty string up
     * to 64 chars (comfortably covers UUID/ULID) is kept as-is; anything else
     * (empty string, array, oversized string) is dropped.
     */
    protected static function normalizeBelongsToKey(mixed $v): int|string|null
    {
        if (is_numeric($v)) {
            return (int) $v;
        }

        if (is_string($v) && $v !== '' && strlen($v) <= 64) {
            return $v;
        }

        return null;
    }

    protected static function normalizeBelongsToValue(BelongsToFilter $filter, mixed $value): ?array
    {
        if ($filter->isMultiple()) {
            if (! is_array($value)) {
                return null;
            }
            $values = array_slice(array_values(array_filter(array_map(
                fn ($v) => self::normalizeBelongsToKey($v),
                $value
            ), fn ($v) => $v !== null)), 0, self::MAX_MULTI_SELECT);

            return $values === [] ? null : ['operator' => 'in', 'value' => $values];
        }

        $key = self::normalizeBelongsToKey($value);

        return $key === null ? null : ['operator' => 'equals', 'value' => $key];
    }
}
