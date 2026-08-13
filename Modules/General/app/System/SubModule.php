<?php

namespace Modules\General\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Translatable('name', 'description')]
class SubModule extends Model
{
    use HasTranslations;

    protected $touches = ['module'];

    protected $fillable = [
        'name',
        'description',
        'code',
        'route',
        'icon',
        'sort_order',
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

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'submodule_id');
    }
}
