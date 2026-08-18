<?php

namespace Modules\Finance\Models\CashAndBanks\Banks;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Database\Factories\BankAccountFactory;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\General\Models\World\Currency;

/**
 * A bank account within a specific bank. SubApplication of Bank.
 * Code pattern: fin-cbn-bnk-{bank-code}-bacc-{slug}
 *
 * @property string $code
 * @property int $bank_id
 * @property string $account_number
 * @property string $account_name
 * @property string $account_type
 * @property int $currency_id
 */
class BankAccount extends Model
{
    use HasFactory;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'bank_id',
        'account_number',
        'account_name',
        'account_type',
        'gl_account_id',
        'currency_id',
        'balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function checksBooks(): HasMany
    {
        return $this->hasMany(ChecksBook::class, 'bank_account_id');
    }

    protected static function booted(): void
    {
        static::creating(function (BankAccount $account): void {
            $builder = app(RecordCodeBuilder::class);
            $bank = $account->bank ?? Bank::find($account->bank_id);

            if (! $bank) {
                throw new \RuntimeException('Bank must exist before creating BankAccount');
            }

            $slug = $builder->uniqueSlug(
                $account->account_number ?? $account->account_name ?? 'account',
                fn (string $s): bool => static::where('code', "{$bank->code}-bacc-{$s}")->exists()
            );

            $account->code = $builder->subApplicationRecordCode($bank->code, 'bacc', $slug);
        });
    }

    protected static function newFactory(): BankAccountFactory
    {
        return BankAccountFactory::new();
    }
}
