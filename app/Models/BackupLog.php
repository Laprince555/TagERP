<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BackupLog extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const APPLICATION_CODE = 'gen-sys-bkp';

    protected $fillable = [
        'user_id',
        'file_path',
        'file_size',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
