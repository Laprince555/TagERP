<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Modules\Finance\Database\Factories\JournalBookFactory;

/**
 * The kind of document a journal is — receipt voucher, payment voucher, sales
 * daybook. It decides numbering and permissions, and holds no balances of its
 * own; that is the ledger's job.
 *
 * @property string $code
 * @property string $name
 * @property string $slug
 * @property string $sequence_prefix
 */
class JournalBook extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'fin-gl-bok';

    protected $table = 'journal_books';

    protected $fillable = [
        'name',
        'sequence_prefix',
        'ledger_scope',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ledger_scope' => LedgerScope::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * The secondary ledgers this book is explicitly routed to. Only consulted
     * when the scope is Selected.
     */
    public function ledgers(): BelongsToMany
    {
        return $this->belongsToMany(Ledger::class, 'journal_book_ledger');
    }

    /**
     * Which secondary ledgers should receive a copy of a journal entered in the
     * given primary ledger.
     *
     * This is where "this entry belongs in the company books but not the tax
     * books" is actually decided.
     *
     * @return Collection<int, Ledger>
     */
    public function targetLedgersFor(Ledger $primary): Collection
    {
        if (! $primary->is_primary) {
            // A journal keyed straight into a secondary ledger is an original in
            // its own right — a tax-only adjustment, say — and is not carried
            // anywhere else.
            return new Collection;
        }

        $secondaries = Ledger::query()
            ->where('primary_ledger_id', $primary->getKey())
            ->where('is_active', true)
            ->get();

        if ($this->ledger_scope === LedgerScope::All) {
            return $secondaries;
        }

        $selected = $this->ledgers()->pluck('ledgers.id')->all();

        return $secondaries->filter(
            fn (Ledger $ledger): bool => in_array($ledger->getKey(), $selected, true)
        )->values();
    }

    protected static function booted(): void
    {
        static::creating(function (JournalBook $book): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($book->slug)) {
                $book->slug = $builder->uniqueSlug($book->name, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($book->code)) {
                $book->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $book->slug);
            }
        });
    }

    protected static function newFactory(): JournalBookFactory
    {
        return JournalBookFactory::new();
    }
}
