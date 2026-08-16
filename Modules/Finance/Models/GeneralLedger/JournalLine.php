<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Finance\Database\Factories\JournalLineFactory;
use Modules\General\Models\World\Currency;
use RuntimeException;

/**
 * One side of one journal. Carries both the amount as entered and the amount
 * in the ledger's base currency, plus the analysis that says who the amount is
 * about.
 *
 * @property string $code
 * @property int $line_number
 */
class JournalLine extends Model
{
    use HasFactory;

    protected $table = 'journal_lines';

    protected $fillable = [
        'journal_id',
        'line_number',
        'account_id',
        'cost_center_id',
        'description',
        'currency_id',
        'exchange_rate',
        'debit',
        'credit',
        'base_debit',
        'base_credit',
        'analysis_type',
        'analysis_id',
        'analysis_code',
        'analysis_name',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:10',
            'debit' => 'decimal:6',
            'credit' => 'decimal:6',
            'base_debit' => 'decimal:6',
            'base_credit' => 'decimal:6',
            'line_number' => 'integer',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function analysis(): MorphTo
    {
        return $this->morphTo('analysis');
    }

    protected static function booted(): void
    {
        static::saving(function (JournalLine $line): void {
            // A line is a debit or a credit, never both. Allowing both would
            // make the same line contribute to each side of the trial balance,
            // which balances arithmetically while describing nothing.
            if ((float) $line->debit > 0 && (float) $line->credit > 0) {
                throw new RuntimeException('A journal line cannot carry a debit and a credit at the same time.');
            }

            if ((float) $line->debit < 0 || (float) $line->credit < 0) {
                throw new RuntimeException('A journal line cannot carry a negative amount; use the opposite side instead.');
            }
        });

        static::creating(function (JournalLine $line): void {
            if (blank($line->code)) {
                $journal = $line->journal ?? Journal::find($line->journal_id);

                $line->code = app(RecordCodeBuilder::class)->subApplicationRecordCode(
                    (string) $journal?->code,
                    'lin',
                    (string) $line->line_number,
                );
            }
        });
    }

    protected static function newFactory(): JournalLineFactory
    {
        return JournalLineFactory::new();
    }
}
