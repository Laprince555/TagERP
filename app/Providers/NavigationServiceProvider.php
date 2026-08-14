<?php

namespace App\Providers;

use App\Services\NavigationTreeService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class NavigationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NavigationTreeService::class, NavigationTreeService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.app', 'layouts.*', 'components.layouts.*'], function (ViewInstance $view): void {
            $view->with('globalNavigationTree', app(NavigationTreeService::class)->getTreeForUser());
        });
    }
}
