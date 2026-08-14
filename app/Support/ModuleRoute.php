<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class ModuleRoute
{
    private const SUB_MODULE_ROUTES_CACHE_KEY = 'navigation.sub-module-routes';

    /**
     * Register the localized index route of an ERP module.
     *
     * @param  string  $name  Route name, which must match the `modules.route` column.
     * @param  class-string|string  $component  Full-page Livewire component class or registered component name.
     */
    public static function registerIndex(string $name, string $path, string $component): void
    {
        Route::middleware(['auth'])
            ->prefix('{locale}')
            ->name($name)
            ->group(function () use ($path, $component): void {
                Route::livewire($path, $component)->name('');
            });
    }

    /**
     * Register the localized page of every sub module belonging to a module.
     *
     * Route names mirror the `sub_modules.route` column verbatim (`general.world`,
     * `finance.gl`) and the URI is derived from that same value, so adding a sub
     * module row is all it takes to publish its page — no per-route branching.
     *
     * Rows are registered regardless of `is_active`; the page itself decides what an
     * inactive sub module looks like, which keeps a disabled sub module distinguishable
     * from a URL that was never a sub module at all.
     *
     * @param  string  $name  Module route name, matching the `modules.route` column.
     * @param  class-string|string  $component  Full-page Livewire component class or registered component name.
     */
    public static function registerSubModules(string $name, string $path, string $component): void
    {
        $subModuleRoutes = self::subModuleRouteNames()[$name] ?? [];

        if ($subModuleRoutes === []) {
            return;
        }

        Route::middleware(['auth'])
            ->prefix('{locale}')
            ->group(function () use ($path, $component, $subModuleRoutes): void {
                foreach ($subModuleRoutes as $routeName => $segment) {
                    Route::livewire($path.'/'.$segment, $component)->name($routeName);
                }
            });
    }

    /**
     * Drop the memoized sub module route table.
     *
     * Called by the navigation observer whenever a navigation record changes. Note that
     * `route:cache` bakes the routes into the compiled route file, so a cached deployment
     * additionally needs `route:clear` before a new sub module becomes reachable.
     */
    public static function forgetSubModuleRoutes(): void
    {
        Cache::forget(self::SUB_MODULE_ROUTES_CACHE_KEY);
    }

    /**
     * Sub module route names grouped by their owning module prefix.
     *
     * Runs once per request at route registration time and is cached forever, so the
     * steady state costs nothing. An empty result is deliberately never cached: seeding
     * sub modules into a fresh database would otherwise leave the routes missing until
     * someone flushed the cache by hand. Any failure (missing table during the very
     * first `migrate`) degrades to "no sub module routes" rather than a fatal boot.
     *
     * @return array<string, array<string, string>>
     */
    private static function subModuleRouteNames(): array
    {
        return rescue(function (): array {
            $cached = Cache::get(self::SUB_MODULE_ROUTES_CACHE_KEY);

            if (is_array($cached) && $cached !== []) {
                return $cached;
            }

            $grouped = self::querySubModuleRouteNames();

            if ($grouped !== []) {
                Cache::forever(self::SUB_MODULE_ROUTES_CACHE_KEY, $grouped);
            }

            return $grouped;
        }, [], report: false);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function querySubModuleRouteNames(): array
    {
        $grouped = [];

        $routeNames = DB::table('sub_modules')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('route');

        foreach ($routeNames as $routeName) {
            // A sub module route is exactly `{module}.{segment}`; anything deeper belongs
            // to an application and is registered by its own module.
            if (! is_string($routeName) || substr_count($routeName, '.') !== 1) {
                continue;
            }

            [$modulePrefix, $segment] = explode('.', $routeName);

            if ($modulePrefix === '' || $segment === '') {
                continue;
            }

            $grouped[$modulePrefix][$routeName] = $segment;
        }

        return $grouped;
    }
}
