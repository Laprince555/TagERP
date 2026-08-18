<?php

namespace Modules\General\System\System;

use App\Models\BackupLog;
use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Vertical-slice provider for the "gen-sys-bkp" Application.
 */
class BackupRecordReferenceProvider implements RecordReferenceProvider
{
    public function applicationCode(): string
    {
        return BackupLog::APPLICATION_CODE;
    }

    public function modelClass(): string
    {
        return BackupLog::class;
    }

    public function identityColumns(): array
    {
        return ['id', 'status', 'created_at'];
    }

    public function cardColumns(): array
    {
        return ['file_size'];
    }

    public function previewColumns(): array
    {
        return ['file_path', 'error_message'];
    }

    public function title(Model $record): string
    {
        /** @var BackupLog $record */
        return 'Backup '.$record->created_at->format('Y-m-d H:i:s');
    }

    public function url(Model $record): ?string
    {
        return route('general.system.backups.show', ['recordId' => $record->getKey()]);
    }

    public function cardFacts(Model $record): array
    {
        /** @var BackupLog $record */
        return [
            new RecordFact('Status', (string) $record->status, 10),
        ];
    }

    public function previewFacts(Model $record): array
    {
        return $this->cardFacts($record);
    }

    public function scopeQuery(Builder $query): Builder
    {
        return $query;
    }

    public function authorize(Model $record): bool
    {
        /** @var BackupLog $record */
        return $record->status === 'success';
    }

    public function cacheTtl(): ?int
    {
        return null;
    }
}
