<?php

namespace Modules\General\System;

use App\Observers\NavigationObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\General\Database\Factories\ModuleFactory;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Translatable('name', 'description')]
class Module extends EloquentModel
{
    use HasFactory;
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    protected static function booted(): void
    {
        static::observe(NavigationObserver::class);
    }

    protected static function newFactory(): ModuleFactory
    {
        return ModuleFactory::new();
    }

    public function subModules(): HasMany
    {
        return $this->hasMany(SubModule::class, 'module_id');
    }
}
