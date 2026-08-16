<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Database\Factories\FiscalPeriodFactory;

/**
 * One period inside a fiscal year. Its open/closed state is not stored here:
 * that belongs to the ledger, since one ledger can close a period while
 * another still needs it open.
 *
 * @property string $code
 * @property string $name
 * @property int $sequence
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property bool $is_adjustment
 */
class FiscalPeriod extends Model
{
    use HasFactory;

    protected $table = 'fiscal_periods';

    protected $fillable = [
        'name',
        'fiscal_year_id',
        'sequence',
        'start_date',
        'end_date',
        'is_adjustment',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'is_adjustment' => 'boolean',
            'sequence' => 'integer',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    public function ledgerStatuses(): HasMany
    {
        return $this->hasMany(LedgerPeriodStatus::class, 'fiscal_period_id');
    }

    public function contains(CarbonImmutable $date): bool
    {
        return $date->betweenIncluded($this->start_date, $this->end_date);
    }

    protected static function booted(): void
    {
        static::creating(function (FiscalPeriod $period): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($period->slug)) {
                $period->slug = $builder->uniqueSlug(
                    $period->name.'-'.$period->fiscal_year_id,
                    fn (string $slug): bool => static::where('slug', $slug)->exists(),
                );
            }

            if (blank($period->code)) {
                $period->code = $builder->applicationRecordCode(FiscalYear::APPLICATION_CODE, $period->slug);
            }
        });
    }

    protected static function newFactory(): FiscalPeriodFactory
    {
        return FiscalPeriodFactory::new();
    }
}
