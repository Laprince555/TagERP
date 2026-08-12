<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'can:manage_users'])->group(function (): void {
    Route::view('/admin/users/create', 'admin.users.create')->name('users.create');
});
