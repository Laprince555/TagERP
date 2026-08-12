<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('{locale}')
    ->name('finance.')
    ->group(function (): void {
        Route::view('/finance', 'finance::index')->name('index');
    });
