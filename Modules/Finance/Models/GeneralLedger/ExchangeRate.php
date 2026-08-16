<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\ExchangeRateFactory;
use Modules\General\Models\World\Currency;

/**
 * @property string $code
 * @property CarbonImmutable $rate_date
 * @property RateType $rate_type
 */
class ExchangeRate extends Model
{
    use HasFactory;

    public const APPLICATION_CODE = 'fin-gl-rat';

    protected $table = 'exchange_rates';

    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate_date',
        'rate',
        'rate_type',
    ];

    protected function casts(): array
    {
        return [
            'rate_date' => 'immutable_date',
            'rate_type' => RateType::class,
            'rate' => 'decimal:10',
        ];
    }

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    /**
     * The rate to use for a conversion on a given date.
     *
     * Falls back to the most recent earlier rate rather than failing: rates are
     * published on working days, and a journal dated on a weekend still has to
     * convert. Same-currency conversions short-circuit to 1 so callers never
     * need a special case.
     */
    public static function resolve(
        int $fromCurrencyId,
        int $toCurrencyId,
        CarbonImmutable $date,
        RateType $type = RateType::Daily,
    ): ?string {
        if ($fromCurrencyId === $toCurrencyId) {
            return '1';
        }

        return static::query()
            ->where('from_currency_id', $fromCurrencyId)
            ->where('to_currency_id', $toCurrencyId)
            ->where('rate_type', $type->value)
            ->whereDate('rate_date', '<=', $date)
            ->orderByDesc('rate_date')
            ->value('rate');
    }

    protected static function booted(): void
    {
        static::creating(function (ExchangeRate $rate): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($rate->slug)) {
                $rate->slug = $builder->uniqueSlug(
                    $rate->from_currency_id.'-'.$rate->to_currency_id.'-'.$rate->rate_type->value.'-'.$rate->rate_date->toDateString(),
                    fn (string $slug): bool => static::where('slug', $slug)->exists(),
                );
            }

            if (blank($rate->code)) {
                $rate->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $rate->slug);
            }
        });
    }

    protected static function newFactory(): ExchangeRateFactory
    {
        return ExchangeRateFactory::new();
    }
}
