<?php

namespace App\Notifications;

use App\Models\Import;
use Illuminate\Notifications\Notification;

class ImportQueued extends Notification
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
        return [
            'type' => 'import',
            'icon' => 'arrow-up-tray',
            'title' => __('Import queued'),
            'body' => __(':file uploaded — importing now.', ['file' => $this->import->filename]),
            'url' => route('imports.show', $this->import),
            'action' => __('View progress'),
        ];
    }
}
