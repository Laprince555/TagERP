<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetUserTheme;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\DynamicRecordView\Resolution\RecordResolver;
use App\Support\DynamicTable\Core\SavedTableViewStore;
use App\Support\DynamicTable\Core\TablePreferenceStore;
use App\Support\DynamicTable\PreferenceStores\EloquentSavedTableViewStore;
use App\Support\DynamicTable\PreferenceStores\EloquentTablePreferenceStore;
use App\Support\RecordReference\RecordReferenceRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider as BaseTelescopeServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(BaseTelescopeServiceProvider::class)) {
            $this->app->register(BaseTelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->bind(TablePreferenceStore::class, EloquentTablePreferenceStore::class);
        $this->app->bind(SavedTableViewStore::class, EloquentSavedTableViewStore::class);

        // Scoped (not a true singleton) so RecordResolver's per-request memoization
        // cache never survives across requests, only within one.
        $this->app->scoped(RecordResolver::class);

        // Scoped, not a true singleton, so provider registration re-runs per
        // request rather than leaking module boot order across requests
        // under octane/queue workers.
        $this->app->scoped(RecordReferenceRegistry::class);

        // Scoped for the same reason as RecordReferenceRegistry above.
        $this->app->scoped(RecordViewRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage_users', fn ($user): bool => $user->hasRole('super_admin'));

        Livewire::addPersistentMiddleware([
            SetLocale::class,
            SetUserTheme::class,
        ]);
    }
}
