<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'table_key', 'name', 'configuration', 'schema_version', 'is_default'])]
class TableView extends Model
{
    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'schema_version' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
