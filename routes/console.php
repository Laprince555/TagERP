<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * EmployeePermissionSynchronizer only runs off Employee saved/deleted events,
 * so any write that bypasses Eloquent (a mass update(), a raw query, a direct
 * grant-table edit) leaves a user's Spatie permissions stale indefinitely.
 * This is the only safety net for that drift.
 */
Schedule::command('hr:permissions:reconcile')
    ->dailyAt('03:00')
    ->onOneServer();
