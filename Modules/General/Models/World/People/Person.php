<?php

namespace Modules\General\Models\World\People;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\General\Database\Factories\PersonFactory;
use Modules\General\Models\World\City;

/**
 * @property string $code
 * @property string $full_name
 * @property string $slug
 */
class Person extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'gen-wld-per';

    protected $fillable = [
        'full_name',
        'nickname',
        'passport_number',
        'national_id',
        'city_id',
        'address',
        'phone',
        'email',
        'bank_account_1',
        'iban_1',
        'bank_account_2',
        'iban_2',
        'date_of_birth',
        'gender',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => Gender::class,
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** Job/role history over time — the "Positions" SubApplication. */
    public function positions(): HasMany
    {
        return $this->hasMany(PersonPosition::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Person $person): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($person->slug)) {
                $base = trim($person->full_name.'-'.($person->national_id ?? $person->passport_number ?? ''), '-');
                $person->slug = $builder->uniqueSlug($base, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($person->code)) {
                $person->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $person->slug);
            }
        });
    }

    protected static function newFactory(): PersonFactory
    {
        return PersonFactory::new();
    }
}
