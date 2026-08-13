<?php

namespace App\Providers;

use App\Observers\NavigationObserver;
use App\Services\NavigationTreeService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Modules\General\System\Application;
use Modules\General\System\Module;
use Modules\General\System\SubModule;

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
        Module::observe(NavigationObserver::class);
        SubModule::observe(NavigationObserver::class);
        Application::observe(NavigationObserver::class);

        View::composer(['layouts.app', 'layouts.*', 'components.layouts.*'], function (ViewInstance $view): void {
            $view->with('globalNavigationTree', app(NavigationTreeService::class)->getTreeForUser());
        });
    }
}
