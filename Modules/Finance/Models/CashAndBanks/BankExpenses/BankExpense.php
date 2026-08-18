<?php

namespace Modules\Finance\Models\CashAndBanks\BankExpenses;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\BankExpenseFactory;
use Modules\Finance\Models\CashAndBanks\Banks\Bank;
use Modules\Finance\Models\CashAndBanks\Banks\BankAccount;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\General\Models\World\Currency;

/**
 * Bank expense (fees, interest, commissions, etc.). Creates GL journal
 * entry when posted.
 *
 * @property string $code
 * @property string $slug
 * @property ?string $number
 * @property int $bank_id
 * @property ?int $bank_account_id
 * @property string $status
 */
class BankExpense extends Model
{
    use HasFactory;

    public const APPLICATION_CODE = 'fin-cbn-bexp';

    protected $table = 'bank_expenses';

    protected $fillable = [
        'number',
        'bank_id',
        'bank_account_id',
        'expense_date',
        'amount',
        'currency_id',
        'expense_type',
        'description',
        'invoice_reference',
        'gl_account_id',
        'status',
        'posted_at',
        'journal_id',
        'reconciliation_status',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:6',
            'posted_at' => 'datetime',
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

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gl_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    protected static function booted(): void
    {
        static::creating(function (BankExpense $expense): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($expense->slug)) {
                $expense->slug = $builder->uniqueSlug(
                    $expense->description ?? $expense->expense_type ?? 'expense',
                    fn (string $slug): bool => static::where('slug', $slug)->exists()
                );
            }

            if (blank($expense->code)) {
                $expense->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $expense->slug);
            }
        });
    }

    protected static function newFactory(): BankExpenseFactory
    {
        return BankExpenseFactory::new();
    }
}
