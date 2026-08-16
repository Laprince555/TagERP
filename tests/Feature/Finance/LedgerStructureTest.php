<?php

use Carbon\CarbonImmutable;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;
use Modules\Finance\Models\GeneralLedger\FiscalYear;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\Finance\Models\GeneralLedger\LedgerConversionType;
use Modules\Finance\Models\GeneralLedger\PeriodStatus;
use Modules\Finance\Models\GeneralLedger\RateType;

it('splits a fiscal year into periods that tile it exactly', function (int $count) {
    $year = FiscalYear::factory()->startingOn(CarbonImmutable::create(2026, 1, 1))->create();

    $periods = $year->generatePeriods($count);

    expect($periods)->toHaveCount($count)
        ->and($periods[0]->start_date->toDateString())->toBe('2026-01-01')
        ->and(end($periods)->end_date->toDateString())->toBe('2026-12-31');

    // No gaps and no overlaps: each period starts the day after the last ended.
    foreach (array_slice($periods, 1) as $index => $period) {
        expect($period->start_date->toDateString())
            ->toBe($periods[$index]->end_date->addDay()->toDateString());
    }
})->with([12, 4, 6, 2, 1]);

it('refuses a period count that cannot divide the year evenly', function () {
    $year = FiscalYear::factory()->startingOn(CarbonImmutable::create(2026, 1, 1))->create();

    expect(fn () => $year->generatePeriods(5))->toThrow(InvalidArgumentException::class);
});

it('adds the adjustment period on the last day of the year', function () {
    $year = FiscalYear::factory()->startingOn(CarbonImmutable::create(2026, 1, 1))->create();

    $periods = $year->generatePeriods(12, withAdjustmentPeriod: true);
    $adjustment = end($periods);

    expect($periods)->toHaveCount(13)
        ->and($adjustment->is_adjustment)->toBeTrue()
        ->and($adjustment->start_date->toDateString())->toBe('2026-12-31')
        ->and($adjustment->end_date->toDateString())->toBe('2026-12-31');
});

it('finds the trading period a date falls in and ignores the adjustment period', function () {
    $year = FiscalYear::factory()->startingOn(CarbonImmutable::create(2026, 1, 1))->create();
    $year->generatePeriods(12, withAdjustmentPeriod: true);

    expect($year->periodFor(CarbonImmutable::create(2026, 3, 15))->sequence)->toBe(3)
        ->and($year->periodFor(CarbonImmutable::create(2026, 12, 31))->sequence)->toBe(12)
        ->and($year->periodFor(CarbonImmutable::create(2027, 1, 1)))->toBeNull();
});

it('treats a period with no stored status as open', function () {
    $ledger = Ledger::factory()->create();
    $year = FiscalYear::factory()->create(['entity_id' => $ledger->entity_id]);
    $period = $year->generatePeriods(12)[0];

    expect($ledger->statusFor($period))->toBe(PeriodStatus::Open)
        ->and($ledger->acceptsPostingsIn($period))->toBeTrue();
});

it('closes and reopens a period per ledger without touching its sibling', function () {
    $entity = Ledger::factory()->create();
    $other = Ledger::factory()->create(['entity_id' => $entity->entity_id]);
    $year = FiscalYear::factory()->create(['entity_id' => $entity->entity_id]);
    $period = $year->generatePeriods(12)[0];

    $entity->closePeriod($period);

    expect($entity->statusFor($period))->toBe(PeriodStatus::Closed)
        ->and($entity->acceptsPostingsIn($period))->toBeFalse()
        ->and($other->statusFor($period))->toBe(PeriodStatus::Open);

    $entity->reopenPeriod($period);

    expect($entity->statusFor($period))->toBe(PeriodStatus::Open);
});

it('never reopens a permanently closed period', function () {
    $ledger = Ledger::factory()->create();
    $year = FiscalYear::factory()->create(['entity_id' => $ledger->entity_id]);
    $period = $year->generatePeriods(12)[0];

    $ledger->closePeriod($period, permanently: true);

    expect(fn () => $ledger->reopenPeriod($period))->toThrow(RuntimeException::class)
        ->and(fn () => $ledger->closePeriod($period))->toThrow(RuntimeException::class);
});

it('refuses a secondary ledger that does not name its primary', function () {
    expect(fn () => Ledger::factory()->create([
        'is_primary' => false,
        'conversion_type' => LedgerConversionType::Currency,
    ]))->toThrow(RuntimeException::class);
});

it('refuses a currency-converting ledger with no rounding account', function () {
    $primary = Ledger::factory()->create();

    expect(fn () => Ledger::factory()->create([
        'entity_id' => $primary->entity_id,
        'is_primary' => false,
        'primary_ledger_id' => $primary->id,
        'conversion_type' => LedgerConversionType::Currency,
        'rounding_account_id' => null,
    ]))->toThrow(RuntimeException::class);
});

it('accepts a fully configured secondary ledger', function () {
    $primary = Ledger::factory()->create();
    $secondary = Ledger::factory()->secondaryOf($primary)->create();

    expect($secondary->is_primary)->toBeFalse()
        ->and($secondary->primaryLedger->id)->toBe($primary->id)
        ->and($secondary->rounding_account_id)->not->toBeNull()
        ->and($primary->secondaryLedgers()->count())->toBe(1);
});

it('refuses a primary ledger that declares a conversion', function () {
    $primary = Ledger::factory()->create();

    expect(fn () => Ledger::factory()->create([
        'is_primary' => true,
        'primary_ledger_id' => $primary->id,
    ]))->toThrow(RuntimeException::class);
});

it('falls back to the latest earlier rate and short-circuits same-currency', function () {
    $rate = ExchangeRate::factory()->create([
        'rate_date' => CarbonImmutable::create(2026, 3, 6),
        'rate' => '48.5',
    ]);

    $onWeekend = ExchangeRate::resolve(
        $rate->from_currency_id,
        $rate->to_currency_id,
        CarbonImmutable::create(2026, 3, 8),
    );

    expect((float) $onWeekend)->toBe(48.5)
        ->and(ExchangeRate::resolve($rate->from_currency_id, $rate->from_currency_id, CarbonImmutable::create(2026, 3, 8)))->toBe('1')
        ->and(ExchangeRate::resolve(
            $rate->from_currency_id,
            $rate->to_currency_id,
            CarbonImmutable::create(2026, 3, 5),
        ))->toBeNull();
});

it('keeps rate types apart for the same day', function () {
    $daily = ExchangeRate::factory()->create([
        'rate_date' => CarbonImmutable::create(2026, 3, 6),
        'rate' => '48.5',
        'rate_type' => RateType::Daily,
    ]);

    ExchangeRate::factory()->create([
        'from_currency_id' => $daily->from_currency_id,
        'to_currency_id' => $daily->to_currency_id,
        'rate_date' => CarbonImmutable::create(2026, 3, 6),
        'rate' => '49.2',
        'rate_type' => RateType::Closing,
    ]);

    $date = CarbonImmutable::create(2026, 3, 6);

    expect((float) ExchangeRate::resolve($daily->from_currency_id, $daily->to_currency_id, $date, RateType::Daily))->toBe(48.5)
        ->and((float) ExchangeRate::resolve($daily->from_currency_id, $daily->to_currency_id, $date, RateType::Closing))->toBe(49.2);
});

it('requires a postable rounding account to exist for the generated journal', function () {
    $primary = Ledger::factory()->create();
    $roundingAccount = Account::factory()->create(['name' => 'FX Rounding']);

    $secondary = Ledger::factory()->create([
        'entity_id' => $primary->entity_id,
        'is_primary' => false,
        'primary_ledger_id' => $primary->id,
        'conversion_type' => LedgerConversionType::Both,
        'rounding_account_id' => $roundingAccount->id,
    ]);

    expect($secondary->roundingAccount->is_postable)->toBeTrue();
});
