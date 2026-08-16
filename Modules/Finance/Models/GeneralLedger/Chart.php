<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Finance\Database\Factories\ChartFactory;
use RuntimeException;

/**
 * A named selection of accounts out of the group-wide catalog. The tree shape
 * itself belongs to Account; a chart only decides which of its nodes are in.
 *
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property int $levels_count
 */
class Chart extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-gl-coa';

    protected $table = 'charts';

    protected $fillable = [
        'name',
        'description',
        'levels_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'levels_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'chart_account');
    }

    /**
     * Attach an account together with every ancestor it needs.
     *
     * Attaching a child without its parents would leave the chart with orphan
     * branches that no rollup could reach, so the ancestors come along
     * automatically rather than being left to whoever is filling the chart in.
     *
     * @throws RuntimeException when the account sits deeper than the chart allows
     */
    public function attachAccount(Account $account): void
    {
        $ancestors = $account->ancestors();
        $depth = count($ancestors) + 1;

        if ($depth > $this->levels_count) {
            throw new RuntimeException(
                "Account [{$account->number}] sits at level {$depth}, deeper than the {$this->levels_count} levels allowed by chart [{$this->code}]."
            );
        }

        $ids = array_merge([$account->getKey()], array_map(
            fn (Account $ancestor): int => $ancestor->getKey(),
            $ancestors,
        ));

        $this->accounts()->syncWithoutDetaching($ids);
    }

    /**
     * Detach an account and everything below it, so removing a branch never
     * strands its children in the chart without a parent.
     */
    public function detachAccount(Account $account): void
    {
        $this->accounts()->detach($this->descendantIds($account));
    }

    /**
     * @return array<int, int>
     */
    private function descendantIds(Account $account): array
    {
        $ids = [$account->getKey()];
        $frontier = [$account->getKey()];

        while ($frontier !== []) {
            $frontier = Account::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    protected static function booted(): void
    {
        static::creating(function (Chart $chart): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($chart->slug)) {
                $chart->slug = $builder->uniqueSlug($chart->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($chart->code)) {
                $chart->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $chart->slug);
            }
        });
    }

    protected static function newFactory(): ChartFactory
    {
        return ChartFactory::new();
    }
}
