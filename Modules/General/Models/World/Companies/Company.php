<?php

namespace Modules\General\Models\World\Companies;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\General\Database\Factories\CompanyFactory;
use Modules\General\Models\World\People\PersonPosition;
use Nnjeim\World\Models\City;

/**
 * @property string $code
 * @property string $name
 * @property string $slug
 */
class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'gen-wld-com';

    protected $fillable = [
        'name',
        'tax_id',
        'commercial_registration',
        'city_id',
        'address',
        'phone',
        'email',
        'logo',
        'bank_account_1',
        'iban_1',
        'bank_account_2',
        'iban_2',
        'website',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(PersonPosition::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($company->slug)) {
                $base = trim($company->name.'-'.$company->tax_id, '-');
                $company->slug = $builder->uniqueSlug($base, fn (string $slug): bool => static::withTrashed()->where('slug', $slug)->exists());
            }

            if (blank($company->code)) {
                $company->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $company->slug);
            }
        });
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }
}
