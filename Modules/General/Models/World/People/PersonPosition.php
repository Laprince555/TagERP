<?php

namespace Modules\General\Models\World\People;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\General\Database\Factories\PersonPositionFactory;
use Modules\General\Models\World\Companies\Company;

/**
 * One stint in a Person's job/role history — the "Positions" SubApplication
 * under Person (general.world.person). Read-only listing for now: the
 * Dynamic Record View engine has no generic "create embedded record" action
 * yet (only Link-existing/Unlink against already-existing rows), so rows
 * are seeded/created through the model directly until that capability
 * exists.
 *
 * @property string $code
 * @property string $slug
 * @property string $position
 */
class PersonPosition extends Model
{
    use HasFactory;

    public const SUBAPPLICATION_SLUG = 'positions';

    protected $fillable = [
        'person_id',
        'company_id',
        'position',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted(): void
    {
        static::creating(function (PersonPosition $position): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($position->slug)) {
                $year = $position->start_date ? Carbon::parse($position->start_date)->format('Y') : '';
                $base = trim($position->position.'-'.$year, '-');
                $position->slug = $builder->uniqueSlug(
                    $base,
                    fn (string $slug): bool => static::where('person_id', $position->person_id)->where('slug', $slug)->exists()
                );
            }

            if (blank($position->code)) {
                /** @var Person $person */
                $person = $position->person()->firstOrFail();
                $position->code = $builder->subApplicationRecordCode($person->code, self::SUBAPPLICATION_SLUG, $position->slug);
            }
        });
    }

    protected static function newFactory(): PersonPositionFactory
    {
        return PersonPositionFactory::new();
    }
}
