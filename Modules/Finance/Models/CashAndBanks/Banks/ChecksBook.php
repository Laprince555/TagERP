<?php

namespace Modules\Finance\Models\CashAndBanks\Banks;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Database\Factories\ChecksBookFactory;

/**
 * A book of checks for a bank account. SubApplication of Bank.
 * Code pattern: fin-cbn-bnk-{bank-code}-cbk-{slug}
 *
 * @property string $code
 * @property int $bank_id
 * @property int $bank_account_id
 * @property int $check_series_start
 * @property int $check_series_end
 * @property int $current_check_number
 */
class ChecksBook extends Model
{
    use HasFactory;

    protected $table = 'checks_books';

    protected $fillable = [
        'bank_id',
        'bank_account_id',
        'check_series_start',
        'check_series_end',
        'current_check_number',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'check_series_start' => 'integer',
            'check_series_end' => 'integer',
            'current_check_number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class, 'checks_book_id');
    }

    protected static function booted(): void
    {
        static::creating(function (ChecksBook $book): void {
            $builder = app(RecordCodeBuilder::class);
            $bank = $book->bank ?? Bank::find($book->bank_id);

            if (! $bank) {
                throw new \RuntimeException('Bank must exist before creating ChecksBook');
            }

            $slug = $builder->uniqueSlug(
                "series-{$book->check_series_start}-{$book->check_series_end}",
                fn (string $s): bool => static::where('code', "{$bank->code}-cbk-{$s}")->exists()
            );

            $book->code = $builder->subApplicationRecordCode($bank->code, 'cbk', $slug);
        });
    }

    protected static function newFactory(): ChecksBookFactory
    {
        return ChecksBookFactory::new();
    }
}
