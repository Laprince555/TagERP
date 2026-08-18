<?php

namespace Modules\HR\Models\Cycles;

use App\Support\Code\RecordCodeBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HR\Database\Factories\Cycles\CycleFactory;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * A reusable approval-workflow template for one subject model (optionally
 * narrowed to one document_type_value of that subject). Its cycle_lines are
 * the ordered stages a CycleTransaction snapshots when it starts.
 *
 * @property string $code
 * @property string $slug
 * @property string $name
 * @property string $subject_model
 * @property ?string $document_type_value
 */
#[Translatable('name')]
class Cycle extends Model
{
    use HasFactory;
    use HasTranslations;

    public const APPLICATION_CODE = 'hr-cyc-cyc';

    protected $table = 'cycles';

    protected $fillable = [
        'cycle_type_id',
        'name',
        'description',
        'subject_model',
        'document_type_value',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function cycleType(): BelongsTo
    {
        return $this->belongsTo(CycleType::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CycleLine::class)->orderBy('sequence');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CycleTransaction::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Cycle $cycle): void {
            $builder = app(RecordCodeBuilder::class);

            if (blank($cycle->slug)) {
                $cycle->slug = $builder->uniqueSlug(
                    $cycle->getTranslation('name', 'en') ?: $cycle->subject_model,
                    fn (string $slug): bool => static::where('slug', $slug)->exists(),
                );
            }

            if (blank($cycle->code)) {
                $cycle->code = $builder->applicationRecordCode(self::APPLICATION_CODE, $cycle->slug);
            }
        });
    }

    protected static function newFactory(): CycleFactory
    {
        return CycleFactory::new();
    }
}
