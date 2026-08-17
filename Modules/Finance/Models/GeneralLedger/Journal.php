<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Models\User;
use App\Support\Code\RecordCodeBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Finance\Database\Factories\JournalFactory;
use Modules\HR\Models\OrganizationStructure\Entity;
use RuntimeException;

/**
 * One accounting document. Immutable once posted — see the guards in booted().
 *
 * @property string $code
 * @property ?string $number
 * @property JournalStatus $status
 * @property CarbonImmutable $journal_date
 */
class Journal extends Model
{
    use HasFactory;

    public const APPLICATION_CODE = 'fin-gl-jou';

    protected $table = 'journals';

    protected $fillable = [
        'ledger_id',
        'journal_book_id',
        'fiscal_period_id',
        'journal_date',
        'description',
        'source_type',
        'source_id',
        'source_reference',
        'source_journal_id',
        'reverses_journal_id',
    ];

    protected function casts(): array
    {
        return [
            'journal_date' => 'immutable_date',
            'posted_at' => 'immutable_datetime',
            'status' => JournalStatus::class,
            'total_debit' => 'decimal:6',
            'total_credit' => 'decimal:6',
        ];
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }

    public function journalBook(): BelongsTo
    {
        return $this->belongsTo(JournalBook::class, 'journal_book_id');
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_id')->orderBy('line_number');
    }

    /**
     * Whose books this journal belongs to, reached through its ledger.
     *
     * Declared here rather than read as `ledger.entity` because the ledger is
     * eager-loaded with a narrowed column list (see LedgerRecordReferenceProvider
     * ::identityColumns()) that excludes entity_id — a nested load through it
     * would silently resolve to null.
     */
    public function entity(): HasOneThrough
    {
        return $this->hasOneThrough(Entity::class, Ledger::class, 'id', 'id', 'ledger_id', 'entity_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source');
    }

    /**
     * The primary-ledger journal this one was generated from, when it lives in
     * a secondary ledger.
     */
    public function sourceJournal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_journal_id');
    }

    public function reversesJournal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_journal_id');
    }

    public function reversal(): HasMany
    {
        return $this->hasMany(self::class, 'reverses_journal_id');
    }

    /**
     * A generated copy is maintained by the replication of its source, so it is
     * never edited or posted on its own.
     */
    public function isGenerated(): bool
    {
        return $this->source_journal_id !== null;
    }

    protected static function booted(): void
    {
        static::creating(function (Journal $journal): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($journal->slug)) {
                $journal->slug = $builder->uniqueSlug(
                    'jv-'.$journal->journal_date->format('Y-m-d').'-'.uniqid(),
                    fn (string $slug): bool => static::where('slug', $slug)->exists(),
                );
            }

            if (blank($journal->code)) {
                $journal->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $journal->slug);
            }
        });

        // A posted journal is a record of something that happened. Correcting it
        // in place would leave the trial balance agreeing with itself while
        // disagreeing with every report already printed from it, so the only
        // legal change after posting is the transition to Reversed.
        static::updating(function (Journal $journal): void {
            $original = $journal->getOriginal('status');

            // A journal created earlier in this same request has no original
            // status at all — the column was filled by its database default —
            // and such a record cannot be posted yet, so the guard has nothing
            // to protect and must not blow up on the empty value.
            $originalStatus = match (true) {
                $original instanceof JournalStatus => $original,
                blank($original) => JournalStatus::Draft,
                default => JournalStatus::from((string) $original),
            };

            if (! $originalStatus->isPosted()) {
                return;
            }

            $changed = array_keys($journal->getDirty());
            $allowed = ['status', 'updated_at'];

            if (array_diff($changed, $allowed) !== []) {
                throw new RuntimeException("Journal [{$journal->code}] is posted and cannot be modified; reverse it instead.");
            }
        });

        static::deleting(function (Journal $journal): void {
            if ($journal->status->isPosted()) {
                throw new RuntimeException("Journal [{$journal->code}] is posted and cannot be deleted; reverse it instead.");
            }
        });
    }

    protected static function newFactory(): JournalFactory
    {
        return JournalFactory::new();
    }
}
