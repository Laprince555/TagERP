<?php

namespace App\Models;

use App\Support\Import\ImportRowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * One queued spreadsheet import of a Dynamic Table, owned by the user who
 * uploaded it. The uploaded file is staged into import_rows first so the
 * progress page has something to show while ImportTableJob works through it.
 *
 * @property string $status
 */
class Import extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'table_class',
        'form_key',
        'filename',
        'path',
        'status',
        'acknowledged_at',
        'error',
        'total_rows',
        'imported_rows',
        'failed_rows',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'total_rows' => 'integer',
            'imported_rows' => 'integer',
            'failed_rows' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /**
     * The user has read the results and no longer needs the source file, so it
     * is deleted from storage. The staged rows stay — they are the record of
     * what happened, and the file they came from is not.
     *
     * Only ever called once the whole file has been worked through: dropping
     * it mid-run would leave a retried job with nothing to resume from.
     */
    public function acknowledge(): void
    {
        if (! $this->isFinished() || $this->isAcknowledged()) {
            return;
        }

        if ($this->path !== '' && Storage::disk('local')->exists($this->path)) {
            Storage::disk('local')->delete($this->path);
        }

        $this->update([
            'path' => '',
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Recounts from the rows themselves rather than incrementing as we go, so
     * a retried job cannot double-count what a previous attempt already wrote.
     */
    public function refreshCounts(): void
    {
        $this->update([
            'imported_rows' => $this->rows()->where('status', ImportRowStatus::Imported)->count(),
            'failed_rows' => $this->rows()->where('status', ImportRowStatus::Failed)->count(),
        ]);
    }
}
