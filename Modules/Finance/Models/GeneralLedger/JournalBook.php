<?php

namespace Modules\Finance\Models\GeneralLedger;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
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
