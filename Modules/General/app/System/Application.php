<?php

namespace Modules\General\System;

use App\Observers\NavigationObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Translatable('name', 'description')]
class Application extends Model
{
    use HasTranslations;

    protected $touches = ['subModule'];

    protected $fillable = [
        'name',
        'description',
        'code',
        'route',
        'icon',
        'sort_order',
        'permission_name',
        'permission_group',
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
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subModule(): BelongsTo
    {
        return $this->belongsTo(SubModule::class);
    }

    public function subApplications(): HasMany
    {
        return $this->hasMany(SubApplication::class, 'application_id');
    }

    protected static function booted(): void
    {
        static::observe(NavigationObserver::class);
    }
}
