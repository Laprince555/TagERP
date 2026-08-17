<?php

namespace App\Notifications;

use App\Models\Import;
use Illuminate\Notifications\Notification;

class ImportFinished extends Notification
{
    public function __construct(public Import $import) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $crashed = $this->import->status === Import::STATUS_FAILED;

        return [
            'type' => $crashed ? 'import-failed' : 'import',
            'icon' => $crashed ? 'exclamation-triangle' : 'check-circle',
            'title' => $crashed ? __('Import failed') : __('Import finished'),
            'body' => $crashed
                ? ($this->import->error ?? __('The file could not be imported.'))
                : __(':file — :imported imported, :failed failed.', [
                    'file' => $this->import->filename,
                    'imported' => $this->import->imported_rows,
                    'failed' => $this->import->failed_rows,
                ]),
            'url' => route('imports.show', $this->import),
            'action' => __('View rows'),
        ];
    }
}
