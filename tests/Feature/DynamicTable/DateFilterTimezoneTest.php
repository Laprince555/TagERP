<?php

use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TableState;
use App\Support\DynamicTable\Query\TableQueryBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

// Every test in this file assumes config('app.timezone') === 'UTC', which is this
// app's actual configured value — asserted explicitly so a future config change
// surfaces as a loud test failure here instead of silently invalidating these cases.
beforeEach(function () {
    expect(config('app.timezone'))->toBe('UTC');

    Schema::create('date_filter_test_events', function ($table) {
        $table->id();
        $table->string('name');
        $table->dateTime('happened_at');
    });
});

class DateFilterTestEvent extends Model
{
    protected $table = 'date_filter_test_events';

    public $timestamps = false;

    protected $fillable = ['name', 'happened_at'];

    protected function casts(): array
    {
        return ['happened_at' => 'datetime'];
    }
}

function dateFilterDefinition(DateFilter $filter): TableDefinition
{
    return new TableDefinition(
        tableKey: 'date-filter-tz-test',
        columns: [TextColumn::make('name')],
        filters: [$filter],
        query: fn () => DateFilterTestEvent::query(),
    );
}

test('a date filter with no explicit timezone defaults to the app timezone', function () {
    $filter = DateFilter::make('happened_at');

    expect($filter->getTimezone())->toBe('UTC');
});

test('an explicit filter timezone is honored', function () {
    $filter = DateFilter::make('happened_at')->timezone('Asia/Tokyo');

    expect($filter->getTimezone())->toBe('Asia/Tokyo');
});

test('day boundaries are computed in the filter timezone, not the database timezone', function () {
    // A user in Asia/Tokyo (UTC+9) filtering "on" 2026-06-15 means their local day,
    // which starts at 2026-06-14 15:00:00 UTC and ends at 2026-06-15 14:59:59 UTC.
    $insideLocalDay = DateFilterTestEvent::create(['name' => 'Inside', 'happened_at' => '2026-06-14 20:00:00']); // 05:00 JST on the 15th
    $beforeLocalDay = DateFilterTestEvent::create(['name' => 'Before', 'happened_at' => '2026-06-14 14:00:00']); // 23:00 JST on the 14th
    $afterLocalDay = DateFilterTestEvent::create(['name' => 'After', 'happened_at' => '2026-06-15 15:30:00']); // 00:30 JST on the 16th

    $filter = DateFilter::make('happened_at')->timezone('Asia/Tokyo');
    $definition = dateFilterDefinition($filter);

    $state = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'on', 'value' => '2026-06-15']]], $definition);
    $rows = (new TableQueryBuilder($definition))->paginate($state);

    expect($rows->pluck('id')->all())->toBe([$insideLocalDay->id])
        ->and($rows->pluck('id'))->not->toContain($beforeLocalDay->id)
        ->and($rows->pluck('id'))->not->toContain($afterLocalDay->id);
});

test('a users timezone differing from the database timezone still resolves correct utc bounds', function () {
    // US Eastern is behind UTC; a full local day converts to a UTC range that starts
    // the previous UTC calendar day.
    $filter = DateFilter::make('happened_at')->timezone('America/New_York');
    $definition = dateFilterDefinition($filter);

    $state = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'on', 'value' => '2026-01-10']]], $definition);

    $expectedStart = Carbon::createFromFormat('Y-m-d', '2026-01-10', 'America/New_York')->startOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s');
    $expectedEnd = Carbon::createFromFormat('Y-m-d', '2026-01-10', 'America/New_York')->endOfDay()->setTimezone('UTC')->format('Y-m-d H:i:s');

    expect($state->filters['happened_at']['value'])->toBe([$expectedStart, $expectedEnd])
        ->and($expectedStart)->toBe('2026-01-10 05:00:00') // EST is UTC-5 in January
        ->and($expectedEnd)->toBe('2026-01-11 04:59:59');
});

test('day boundaries correctly cross a daylight saving time transition', function () {
    // 2026-03-08 is the US spring-forward DST transition (clocks jump 2am -> 3am EST->EDT).
    $filter = DateFilter::make('happened_at')->timezone('America/New_York');
    $definition = dateFilterDefinition($filter);

    $state = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'on', 'value' => '2026-03-08']]], $definition);

    // A naive 24-hour assumption would be wrong here — this DST day is only 23 hours long.
    [$start, $end] = $state->filters['happened_at']['value'];
    $durationSeconds = (int) abs(Carbon::parse($end, 'UTC')->diffInSeconds(Carbon::parse($start, 'UTC'))) + 1;

    expect($durationSeconds)->toBe(23 * 3600);
});

test('between is inclusive of the full start and end days', function () {
    $firstDay = DateFilterTestEvent::create(['name' => 'First Day End', 'happened_at' => '2026-01-01 23:59:59']);
    $lastDay = DateFilterTestEvent::create(['name' => 'Last Day Start', 'happened_at' => '2026-01-31 00:00:00']);
    $before = DateFilterTestEvent::create(['name' => 'Before Range', 'happened_at' => '2025-12-31 23:59:59']);
    $after = DateFilterTestEvent::create(['name' => 'After Range', 'happened_at' => '2026-02-01 00:00:00']);

    $filter = DateFilter::make('happened_at'); // UTC, matches DB timezone
    $definition = dateFilterDefinition($filter);

    $state = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'between', 'value' => ['2026-01-01', '2026-01-31']]]], $definition);
    $rows = (new TableQueryBuilder($definition))->paginate($state);

    expect($rows->pluck('id')->sort()->values()->all())->toBe([$firstDay->id, $lastDay->id])
        ->and($rows->pluck('id'))->not->toContain($before->id)
        ->and($rows->pluck('id'))->not->toContain($after->id);
});

test('before and after are exclusive at the day boundary while before_or_on and after_or_on are inclusive', function () {
    $onBoundary = DateFilterTestEvent::create(['name' => 'On Boundary', 'happened_at' => '2026-05-10 12:00:00']);

    $filter = DateFilter::make('happened_at');
    $definition = dateFilterDefinition($filter);

    $before = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'before', 'value' => '2026-05-10']]], $definition);
    $beforeOrOn = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'before_or_on', 'value' => '2026-05-10']]], $definition);
    $after = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'after', 'value' => '2026-05-10']]], $definition);
    $afterOrOn = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'after_or_on', 'value' => '2026-05-10']]], $definition);

    $builder = new TableQueryBuilder($definition);

    expect($builder->paginate($before)->pluck('id'))->not->toContain($onBoundary->id)
        ->and($builder->paginate($beforeOrOn)->pluck('id'))->toContain($onBoundary->id)
        ->and($builder->paginate($after)->pluck('id'))->not->toContain($onBoundary->id)
        ->and($builder->paginate($afterOrOn)->pluck('id'))->toContain($onBoundary->id);
});

test('an invalid date string is rejected rather than loosely parsed', function () {
    $filter = DateFilter::make('happened_at');
    $definition = dateFilterDefinition($filter);

    // Carbon::parse() would loosely accept all of these; strict createFromFormat() must not.
    foreach (['not-a-date', '2026-13-01', '2026/01/15', '15-01-2026', 'next monday', ''] as $invalid) {
        $state = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'on', 'value' => $invalid]]], $definition);
        expect($state->filters)->not->toHaveKey('happened_at');
    }
});

test('an auto corrected invalid calendar date like february 30th is rejected via round trip validation', function () {
    $filter = DateFilter::make('happened_at');
    $definition = dateFilterDefinition($filter);

    // Carbon::createFromFormat('Y-m-d', '2026-02-30') "succeeds" by rolling over to March 2 —
    // the round-trip format check must catch that the input string doesn't match back.
    $state = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'on', 'value' => '2026-02-30']]], $definition);

    expect($state->filters)->not->toHaveKey('happened_at');
});

test('date filter distinguishes day precision from datetime precision via withTime', function () {
    $exact = DateFilterTestEvent::create(['name' => 'Exact Match', 'happened_at' => '2026-07-04 15:30:00']);
    $sameDayDifferentTime = DateFilterTestEvent::create(['name' => 'Same Day', 'happened_at' => '2026-07-04 09:00:00']);

    $filter = DateFilter::make('happened_at')->withTime();
    $definition = dateFilterDefinition($filter);

    $state = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'after_or_on', 'value' => '2026-07-04T15:30']]], $definition);
    $rows = (new TableQueryBuilder($definition))->paginate($state);

    expect($rows->pluck('id'))->toContain($exact->id)
        ->and($rows->pluck('id'))->not->toContain($sameDayDifferentTime->id);
});

test('a malformed datetime string is rejected when withTime is enabled', function () {
    $filter = DateFilter::make('happened_at')->withTime();
    $definition = dateFilterDefinition($filter);

    // Missing the required time-of-day component for a withTime() filter.
    $state = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'on', 'value' => '2026-07-04']]], $definition);

    expect($state->filters)->not->toHaveKey('happened_at');
});

test('today is computed relative to the filters timezone not the database timezone', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 15, 1, 0, 0, 'UTC')); // 01:00 UTC = 10:00 JST same day, but 20:00 EST the PREVIOUS day

    $lateUtcYesterdayInTokyo = DateFilterTestEvent::create(['name' => 'Tokyo Today', 'happened_at' => Carbon::now('UTC')->toDateTimeString()]);

    $filter = DateFilter::make('happened_at')->timezone('Asia/Tokyo');
    $definition = dateFilterDefinition($filter);

    $state = TableState::normalize(['filters' => ['happened_at' => ['operator' => 'today', 'value' => null]]], $definition);
    $rows = (new TableQueryBuilder($definition))->paginate($state);

    expect($rows->pluck('id'))->toContain($lateUtcYesterdayInTokyo->id);

    Carbon::setTestNow();
});
