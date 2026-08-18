<?php

namespace Modules\Finance\Models\Tax;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\TaxGlLinkFactory;
use Modules\Finance\Models\GeneralLedger\Account;
use RuntimeException;

/**
 * A GL routing rule for tax lines. `tax_category_id`/`tax_id` are nullable
 * dimensions: a null dimension is that dimension's fallback when resolving
 * which account a tax amount posts to (exact match first, then partial-null,
 * then the all-null default rule).
 *
 * @property string $code
 */
class TaxGlLink extends Model
{
    use HasFactory;

    public const APPLICATION_CODE = 'fin-tax-gll';

    protected $table = 'finance_tax_gl_links';

    protected $fillable = [
        'tax_category_id',
        'tax_id',
        'account_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function taxCategory(): BelongsTo
    {
        return $this->belongsTo(TaxCategory::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    protected static function booted(): void
    {
        static::creating(function (TaxGlLink $link): void {
            if (blank($link->code)) {
                $link->code = app(RecordCodeBuilder::class)->applicationRecordCode(self::APPLICATION_CODE, (string) str()->random(8));
            }
        });

        static::saving(function (TaxGlLink $link): void {
            // The DB unique(tax_category_id, tax_id) doesn't catch this: MySQL
            // treats NULLs as distinct, so two rows with the same null
            // dimensions would otherwise both "match" when resolving an account.
            $duplicate = static::query()
                ->when($link->exists, fn ($query) => $query->whereKeyNot($link->getKey()))
                ->when(
                    $link->tax_category_id === null,
                    fn ($query) => $query->whereNull('tax_category_id'),
                    fn ($query) => $query->where('tax_category_id', $link->tax_category_id),
                )
                ->when(
                    $link->tax_id === null,
                    fn ($query) => $query->whereNull('tax_id'),
                    fn ($query) => $query->where('tax_id', $link->tax_id),
                )
                ->exists();

            if ($duplicate) {
                throw new RuntimeException('A GL routing rule for this tax category and tax combination already exists.');
            }
        });
    }

    protected static function newFactory(): TaxGlLinkFactory
    {
        return TaxGlLinkFactory::new();
    }
}
