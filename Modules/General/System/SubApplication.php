<?php

namespace Modules\General\System;

use App\Support\RecordReference\ApplicationColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Translatable('name', 'description')]
class SubApplication extends Model
{
    use HasTranslations;

    protected $fillable = [
        'application_id',
        'name',
        'description',
        'code',
        'route',
        'icon',
        'color',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'color' => ApplicationColor::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
