<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('{locale}')
    ->name('projects.')
    ->group(function (): void {
        Route::view('/projects', 'projects::index')->name('index');
    });
