<?php

namespace App\Models;

use App\Support\Import\ImportRowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One spreadsheet line, staged verbatim before anything is written to the
 * target model. Keeping the raw payload is what makes the import page able to
 * show a failed line next to the reason it failed.
 *
 * @property array<string, mixed> $payload
 */
class ImportRow extends Model
{
    protected $fillable = [
        'import_id',
        'row_number',
        'payload',
        'status',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => ImportRowStatus::class,
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
