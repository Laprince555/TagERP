<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class TableExportFailed extends Notification
{
    public function __construct(public string $reason) {}

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
            'type' => 'export-failed',
            'icon' => 'exclamation-triangle',
            'title' => __('Export failed'),
            'body' => $this->reason,
        ];
    }
}
