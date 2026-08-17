<?php

namespace App\Livewire\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    /** Newest first; the panel is a peek, not an archive. */
    public const LIMIT = 12;

    public function markRead(string $id): void
    {
        $this->notification($id)?->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function delete(string $id): void
    {
        $this->notification($id)?->delete();
    }

    /** Cheap refresh hook for anything that just queued work. */
    #[On('export-queued')]
    #[On('import-queued')]
    public function refreshNotifications(): void {}

    protected function notification(string $id): ?object
    {
        return auth()->user()?->notifications()->whereKey($id)->first();
    }

    public function render(): View
    {
        $notifications = auth()->check()
            ? auth()->user()->notifications()->latest()->limit(self::LIMIT)->get()
            : new Collection;

        return view('livewire.components.notification-bell', [
            'notifications' => $notifications,
            'unreadCount' => auth()->check() ? auth()->user()->unreadNotifications()->count() : 0,
        ]);
    }
}
