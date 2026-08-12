<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('{locale}')
    ->name('hr.')
    ->group(function (): void {
        Route::view('/hr', 'hr::index')->name('index');
    });
