<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('{locale}')
    ->name('general.')
    ->group(function (): void {
        Route::view('/general', 'general::index')->name('index');
    });
