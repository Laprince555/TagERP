<?php

use App\Livewire\Account\ProfilePage;
use App\Livewire\Account\SettingsPage;
use App\Livewire\AppLauncher;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:manage_users'])->group(function (): void {
    Route::view('/admin/users/create', 'admin.users.create')->name('users.create');
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('/', AppLauncher::class)->name('launcher');
    Route::get('/profile', ProfilePage::class)->name('profile');
    Route::get('/settings', SettingsPage::class)->name('settings');
});
