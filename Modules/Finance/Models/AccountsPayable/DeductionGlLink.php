<?php

namespace Modules\Finance\Models\AccountsPayable;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\DeductionGlLinkFactory;
use Modules\Finance\Models\GeneralLedger\Account;
use RuntimeException;

/**
 * A GL routing rule for AP deductions. `deduction_category_id`/`deduction_id`
 * are nullable dimensions: a null dimension is that dimension's fallback
 * when resolving which account a deduction posts to (exact match first,
 * then partial-null, then the all-null default rule).
 *
 * @property string $code
 */
class DeductionGlLink extends Model
{
    use HasFactory;

    public const APPLICATION_CODE = 'fin-ap-dgl';

    protected $table = 'finance_ap_deduction_gl_links';

    protected $fillable = [
        'deduction_category_id',
        'deduction_id',
        'account_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function deductionCategory(): BelongsTo
    {
        return $this->belongsTo(DeductionCategory::class);
    }

    public function deduction(): BelongsTo
    {
        return $this->belongsTo(Deduction::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    protected static function booted(): void
    {
        static::creating(function (DeductionGlLink $link): void {
            if (blank($link->code)) {
                $link->code = app(RecordCodeBuilder::class)->applicationRecordCode(self::APPLICATION_CODE, (string) str()->random(8));
            }
        });

        static::saving(function (DeductionGlLink $link): void {
            // The DB unique(deduction_category_id, deduction_id) doesn't catch
            // this: MySQL treats NULLs as distinct, so two rows with the same
            // null dimensions would otherwise both "match" when resolving an
            // account.
            $duplicate = static::query()
                ->when($link->exists, fn ($query) => $query->whereKeyNot($link->getKey()))
                ->when(
                    $link->deduction_category_id === null,
                    fn ($query) => $query->whereNull('deduction_category_id'),
                    fn ($query) => $query->where('deduction_category_id', $link->deduction_category_id),
                )
                ->when(
                    $link->deduction_id === null,
                    fn ($query) => $query->whereNull('deduction_id'),
                    fn ($query) => $query->where('deduction_id', $link->deduction_id),
                )
                ->exists();

            if ($duplicate) {
                throw new RuntimeException('A GL routing rule for this deduction category and deduction combination already exists.');
            }
        });
    }

    protected static function newFactory(): DeductionGlLinkFactory
    {
        return DeductionGlLinkFactory::new();
    }
}
