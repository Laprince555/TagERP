<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('{locale}')
    ->name('procurement.')
    ->group(function (): void {
        Route::view('/procurement', 'procurement::index')->name('index');
    });
