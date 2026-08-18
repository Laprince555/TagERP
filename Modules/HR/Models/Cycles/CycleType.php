<?php

namespace Modules\HR\Models\Cycles;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HR\Database\Factories\Cycles\CycleTypeFactory;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * Classifies a Cycle and names which Application/SubModule creates the
 * subject documents it governs (e.g. AP Invoices).
 *
 * @property string $code
 * @property string $name
 * @property string $application_code
 */
#[Translatable('name')]
class CycleType extends Model
{
    use HasFactory;
    use HasTranslations;

    public const APPLICATION_CODE = 'hr-cyc-typ';

    protected $table = 'cycle_types';

    protected $fillable = [
        'application_code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class);
    }

    protected static function booted(): void
    {
        static::creating(function (CycleType $cycleType): void {
            if (blank($cycleType->code)) {
                $builder = app(RecordCodeBuilder::class);
                $slug = $builder->uniqueSlug(
                    $cycleType->getTranslation('name', 'en') ?: $cycleType->application_code,
                    fn (string $slug): bool => static::where('code', self::APPLICATION_CODE.'-'.$slug)->exists(),
                );

                $cycleType->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $slug);
            }
        });
    }

    protected static function newFactory(): CycleTypeFactory
    {
        return CycleTypeFactory::new();
    }
}
