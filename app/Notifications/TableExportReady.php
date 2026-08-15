<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class TableExportReady extends Notification
{
    public function __construct(
        public string $path,
        public string $filename,
        public int $rows,
    ) {}

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
        return [
            'type' => 'export',
            'icon' => 'arrow-down-tray',
            'title' => __('Export ready'),
            'body' => __(':file — :count rows', ['file' => $this->filename, 'count' => $this->rows]),
            'path' => $this->path,
            'filename' => $this->filename,
        ];
    }
}
