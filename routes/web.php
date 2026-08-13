<?php

use App\Livewire\AppLauncher;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'can:manage_users'])->group(function (): void {
    Route::view('/admin/users/create', 'admin.users.create')->name('users.create');
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('/launcher', AppLauncher::class)->name('launcher');
    Route::get('/dashboard', function () {
        return redirect('/launcher');
    })->name('dashboard');
});
