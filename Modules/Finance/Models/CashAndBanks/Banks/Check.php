<?php

namespace Modules\Finance\Models\CashAndBanks\Banks;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\CheckFactory;
use Modules\Finance\Models\GeneralLedger\Journal;

/**
 * A single check within a checks book. SubApplication of Bank.
 * Code pattern: fin-cbn-bnk-{bank-code}-chk-{slug}
 *
 * @property string $code
 * @property int $checks_book_id
 * @property int $bank_id
 * @property int $check_number
 * @property string $status
 */
class Check extends Model
{
    use HasFactory;

    protected $table = 'checks';

    protected $fillable = [
        'checks_book_id',
        'bank_id',
        'check_number',
        'check_date',
        'amount',
        'payee_name',
        'description',
        'status',
        'journal_id',
    ];

    protected function casts(): array
    {
        return [
            'check_date' => 'date',
            'amount' => 'decimal:6',
        ];
    }

    public function checksBook(): BelongsTo
    {
        return $this->belongsTo(ChecksBook::class, 'checks_book_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Check $check): void {
            $builder = app(RecordCodeBuilder::class);
            $bank = $check->bank ?? Bank::find($check->bank_id);

            if (! $bank) {
                throw new \RuntimeException('Bank must exist before creating Check');
            }

            $slug = $builder->uniqueSlug(
                "chk-{$check->check_number}",
                fn (string $s): bool => static::where('code', "{$bank->code}-chk-{$s}")->exists()
            );

            $check->code = $builder->subApplicationRecordCode($bank->code, 'chk', $slug);
        });
    }

    protected static function newFactory(): CheckFactory
    {
        return CheckFactory::new();
    }
}
