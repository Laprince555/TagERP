<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\LedgerFactory;
use Modules\General\Models\World\Currency;
use Modules\HR\Models\OrganizationStructure\Entity;
use RuntimeException;

/**
 * The container balances live in: one chart, one base currency, one entity.
 * A secondary ledger restates the primary's entries in a different currency
 * and/or chart; it is never keyed into directly.
 *
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property bool $is_primary
 * @property ?LedgerConversionType $conversion_type
 * @property RateType $rate_type
 */
class Ledger extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-gl-led';

    protected $table = 'ledgers';

    protected $fillable = [
        'name',
        'entity_id',
        'chart_id',
        'base_currency_id',
        'is_primary',
        'primary_ledger_id',
        'conversion_type',
        'rate_type',
        'rounding_account_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'conversion_type' => LedgerConversionType::class,
            'rate_type' => RateType::class,
            'is_active' => 'boolean',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }

    public function chart(): BelongsTo
    {
        return $this->belongsTo(Chart::class, 'chart_id');
    }

    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    public function primaryLedger(): BelongsTo
    {
        return $this->belongsTo(self::class, 'primary_ledger_id');
    }

    public function secondaryLedgers(): HasMany
    {
        return $this->hasMany(self::class, 'primary_ledger_id');
    }

    public function roundingAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'rounding_account_id');
    }

    public function periodStatuses(): HasMany
    {
        return $this->hasMany(LedgerPeriodStatus::class, 'ledger_id');
    }

    /**
     * This ledger's view of a period. No stored row means open.
     */
    public function statusFor(FiscalPeriod $period): PeriodStatus
    {
        return $this->periodStatuses()
            ->where('fiscal_period_id', $period->getKey())
            ->first()?->status ?? PeriodStatus::Open;
    }

    public function acceptsPostingsIn(FiscalPeriod $period): bool
    {
        return $this->statusFor($period)->acceptsPostings();
    }

    public function closePeriod(FiscalPeriod $period, bool $permanently = false): void
    {
        $current = $this->statusFor($period);

        if ($current === PeriodStatus::PermanentlyClosed) {
            throw new RuntimeException("Period [{$period->code}] is permanently closed in ledger [{$this->code}].");
        }

        $this->periodStatuses()->updateOrCreate(
            ['fiscal_period_id' => $period->getKey()],
            ['status' => $permanently ? PeriodStatus::PermanentlyClosed : PeriodStatus::Closed],
        );
    }

    public function reopenPeriod(FiscalPeriod $period): void
    {
        $current = $this->statusFor($period);

        if (! $current->isReopenable() && $current !== PeriodStatus::Open) {
            throw new RuntimeException("Period [{$period->code}] cannot be reopened in ledger [{$this->code}].");
        }

        $this->periodStatuses()->where('fiscal_period_id', $period->getKey())->delete();
    }

    protected static function booted(): void
    {
        static::creating(function (Ledger $ledger): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($ledger->slug)) {
                $ledger->slug = $builder->uniqueSlug($ledger->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($ledger->code)) {
                $ledger->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $ledger->slug);
            }
        });

        static::saving(function (Ledger $ledger): void {
            $ledger->assertConsistentConfiguration();
        });
    }

    /**
     * A ledger is either a primary that stands alone, or a secondary that
     * declares both its source and how it differs from it. Half-configured
     * secondaries are the failure that only shows up at posting time, when a
     * generated journal has nowhere to go, so they are refused at save time.
     *
     * @throws RuntimeException
     */
    protected function assertConsistentConfiguration(): void
    {
        if ($this->is_primary) {
            if ($this->primary_ledger_id !== null || $this->conversion_type !== null) {
                throw new RuntimeException('A primary ledger cannot declare a source ledger or a conversion type.');
            }

            return;
        }

        if ($this->primary_ledger_id === null) {
            throw new RuntimeException('A secondary ledger must name the primary ledger it is fed from.');
        }

        if ($this->conversion_type === null) {
            throw new RuntimeException('A secondary ledger must declare how it differs from its primary.');
        }

        // Line-by-line conversion leaves the copy out of balance by a rounding
        // unit or two, and an unbalanced journal cannot post. Without somewhere
        // to put that difference the secondary ledger silently stops receiving
        // entries, so the account is required up front.
        if ($this->conversion_type->convertsCurrency() && $this->rounding_account_id === null) {
            throw new RuntimeException('A currency-converting ledger must nominate a rounding difference account.');
        }
    }

    protected static function newFactory(): LedgerFactory
    {
        return LedgerFactory::new();
    }
}
