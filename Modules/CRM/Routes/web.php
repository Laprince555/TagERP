<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('{locale}')
    ->name('crm.')
    ->group(function (): void {
        Route::view('/crm', 'crm::index')->name('index');
    });
