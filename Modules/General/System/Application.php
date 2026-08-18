<?php

namespace Modules\General\System;

use App\Observers\NavigationObserver;
use App\Support\RecordReference\ApplicationColor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\General\Database\Factories\ApplicationFactory;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Translatable('name', 'description', 'application_group')]
class Application extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $touches = ['subModule'];

    protected $fillable = [
        'name',
        'description',
        'code',
        'route',
        'icon',
        'color',
        'application_group',
        'sort_order',
        'permission_name',
        'permission_group',
        'custom_actions',
        'is_active',
        'submodule_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'application_group' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'color' => ApplicationColor::class,
            'custom_actions' => 'array',
        ];
    }

    public function subModule(): BelongsTo
    {
        return $this->belongsTo(SubModule::class, 'submodule_id');
    }

    public function subApplications(): HasMany
    {
        return $this->hasMany(SubApplication::class);
    }

    protected static function booted(): void
    {
        static::observe(NavigationObserver::class);
    }

    protected static function newFactory(): ApplicationFactory
    {
        return ApplicationFactory::new();
    }
}
