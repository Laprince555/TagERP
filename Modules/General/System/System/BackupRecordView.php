<?php

namespace Modules\General\System\System;

use App\Models\BackupLog;
use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordAction;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;

class BackupRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.system.backup';

    public function model(): string
    {
        return BackupLog::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(BackupLog::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return BackupLog::query()->whereRaw('1 = 0');
        }

        return BackupLog::query();
    }

    public function applicationCode(): ?string
    {
        return BackupLog::APPLICATION_CODE;
    }

    public function title(mixed $record): string
    {
        return 'Backup '.$record->created_at->format('Y-m-d H:i:s');
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->status === 'success' ? 'Success' : 'Failed';
    }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')
                ->label('Overview')
                ->default()
                ->contents([
                    FieldsContent::make('basic-information')
                        ->heading('Backup Information')
                        ->fields([
                            RecordReferenceViewField::make('user')
                                ->applicationCode('gen-sys-usr')
                                ->relation('user')
                                ->label('Created By'),
                            TextViewField::make('status')->label('Status'),
                            TextViewField::make('file_path')->label('File Path')->copyable(),
                            TextViewField::make('file_size')->label('File Size'),
                            TextViewField::make('created_at')->label('Created At'),
                            TextViewField::make('error_message')->label('Error Message'),
                        ]),
                ]),
        ];
    }

    public function actions(): array
    {
        return [
            RecordAction::make('download')
                ->label('Download')
                ->url(fn ($record) => route('backup.download', $record->id)),
            RecordAction::make('delete')
                ->label('Delete')
                ->url(fn ($record) => route('backup.delete', $record->id)),
        ];
    }
}
