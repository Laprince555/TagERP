<?php

namespace Modules\General\System;

use App\Observers\NavigationObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\General\Database\Factories\SubModuleFactory;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Translatable('name', 'description')]
class SubModule extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $touches = ['module'];

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
        'module_id',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::observe(NavigationObserver::class);
    }

    protected static function newFactory(): SubModuleFactory
    {
        return SubModuleFactory::new();
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'submodule_id');
    }
}
