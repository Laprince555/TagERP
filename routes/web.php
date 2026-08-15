<?php

use App\Livewire\Account\ProfilePage;
use App\Livewire\Account\SettingsPage;
use App\Livewire\AppLauncher;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware(['auth', 'can:manage_users'])->group(function (): void {
    Route::view('/admin/users/create', 'admin.users.create')->name('users.create');
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('/', AppLauncher::class)->name('launcher');
    Route::get('/profile', ProfilePage::class)->name('profile');
    Route::get('/settings', SettingsPage::class)->name('settings');

    /**
     * Ownership is the whole gate: the path is read off the caller's own
     * notification row, so another user's export is a 404 and the stored path
     * never comes from the URL.
     */
    Route::get('/exports/{notification}', function (string $notification) {
        $record = auth()->user()->notifications()->whereKey($notification)->firstOrFail();
        $path = $record->data['path'] ?? null;

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        $record->markAsRead();

        return Storage::disk('local')->download($path, $record->data['filename'] ?? 'export.csv');
    })->name('exports.download');
});
