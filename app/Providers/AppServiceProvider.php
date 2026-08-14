<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetUserTheme;
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
