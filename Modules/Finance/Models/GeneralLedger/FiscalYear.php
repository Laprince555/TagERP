<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use Modules\Finance\Database\Factories\FiscalYearFactory;
use Modules\HR\Models\OrganizationStructure\Entity;

/**
 * One financial year of an entity. The entity's own
 * fiscal_year_start_month/day decides where a year begins, so no separate
 * calendar table restates it.
 *
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 */
class FiscalYear extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-gl-fyr';

    protected $table = 'fiscal_years';

    protected $fillable = [
        'name',
        'entity_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'is_active' => 'boolean',
        ];
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class, 'fiscal_year_id')->orderBy('sequence');
    }

    /**
     * Split the year into equal trading periods, optionally followed by an
     * adjustment period on the last day.
     *
     * The periods must tile the year exactly — no gaps, no overlaps — because
     * a journal date that falls in no period could never be posted, and one
     * that falls in two would be ambiguous. That is why the month count has to
     * divide evenly rather than the remainder being quietly absorbed.
     *
     * @param  int  $count  12 for monthly, 4 for quarterly
     * @return array<int, FiscalPeriod>
     *
     * @throws InvalidArgumentException when the year cannot be split evenly
     */
    public function generatePeriods(int $count = 12, bool $withAdjustmentPeriod = false): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('A fiscal year needs at least one period.');
        }

        $months = $this->start_date->diffInMonths($this->end_date->addDay());

        if ($months % $count !== 0) {
            throw new InvalidArgumentException(
                "A {$months}-month fiscal year cannot be split into {$count} equal periods."
            );
        }

        $monthsPerPeriod = intdiv($months, $count);
        $periods = [];
        $cursor = $this->start_date;

        for ($sequence = 1; $sequence <= $count; $sequence++) {
            $end = $cursor->addMonths($monthsPerPeriod)->subDay();

            $periods[] = $this->periods()->create([
                'name' => $cursor->format('M Y'),
                'sequence' => $sequence,
                'start_date' => $cursor,
                'end_date' => $end,
                'is_adjustment' => false,
            ]);

            $cursor = $end->addDay();
        }

        if ($withAdjustmentPeriod) {
            $periods[] = $this->periods()->create([
                'name' => __('Adjustment').' '.$this->end_date->format('Y'),
                'sequence' => $count + 1,
                'start_date' => $this->end_date,
                'end_date' => $this->end_date,
                'is_adjustment' => true,
            ]);
        }

        return $periods;
    }

    /**
     * The period a given date falls in, or null when the date is outside the year.
     */
    public function periodFor(CarbonImmutable $date): ?FiscalPeriod
    {
        return $this->periods()
            ->where('is_adjustment', false)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    protected static function booted(): void
    {
        static::creating(function (FiscalYear $year): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($year->slug)) {
                $year->slug = $builder->uniqueSlug($year->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($year->code)) {
                $year->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $year->slug);
            }
        });
    }

    protected static function newFactory(): FiscalYearFactory
    {
        return FiscalYearFactory::new();
    }
}
