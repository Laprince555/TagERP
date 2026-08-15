<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetUserTheme;
use App\Support\DynamicForm\Core\FormDefinitionRegistry;
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

        // Singletons, not scoped: modules fill these from their ServiceProvider
        // boot(), which runs once per process. A worker forgets scoped instances
        // between jobs but never re-boots providers, so a scoped registry came
        // back empty for every job after the first ("unknown application code").
        // Contents are static class registrations — nothing request-specific.
        $this->app->singleton(RecordReferenceRegistry::class);
        $this->app->singleton(RecordViewRegistry::class);
        $this->app->singleton(FormDefinitionRegistry::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Unconditional bypass for super admins; returning null (not false) for
        // everyone else lets every other Gate::define/Policy check run normally.
        Gate::before(fn ($user) => $user->hasRole('super_admin') ? true : null);

        Gate::define('manage_users', fn ($user): bool => $user->hasRole('super_admin'));

        Livewire::addPersistentMiddleware([
            SetLocale::class,
            SetUserTheme::class,
        ]);
    }
}
