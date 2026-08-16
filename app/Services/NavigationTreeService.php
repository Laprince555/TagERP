<?php

namespace App\Services;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Route;
use Modules\General\System\Application;
use Modules\General\System\Module;
use Modules\General\System\SubModule;
use Throwable;

class NavigationTreeService
{
    protected ?array $cachedTree = null;

    public function __construct(
        protected CacheManager $cache,
        protected AuthFactory $auth,
    ) {}

    public function getTreeForUser(): array
    {
        if ($this->cachedTree !== null) {
            return $this->cachedTree;
        }

        $cacheKey = $this->buildCacheKey();
        $cacheRepository = $this->cacheRepository();

        return $this->cachedTree = $cacheRepository->rememberForever($cacheKey, function (): array {
            $locale = app()->getLocale();
            $shouldLoadApplications = class_exists('Modules\\General\\System\\Application');

            $modules = Module::query()
                ->select([
                    'id',
                    'name',
                    'description',
                    'code',
                    'route',
                    'icon',
                    'sort_order',
                    'is_active',
                    'permission_name',
                ])
                ->where('is_active', true)
                ->with([
                    'subModules' => function ($query): void {
                        $query
                            ->select([
                                'id',
                                'module_id',
                                'name',
                                'description',
                                'code',
                                'route',
                                'icon',
                                'sort_order',
                                'is_active',
                                'permission_name',
                            ])
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->with([
                                'applications' => function ($applicationQuery): void {
                                    $applicationQuery
                                        ->select([
                                            'id',
                                            'submodule_id',
                                            'name',
                                            'description',
                                            'code',
                                            'route',
                                            'icon',
                                            'application_group',
                                            'sort_order',
                                            'is_active',
                                            'permission_name',
                                        ])
                                        ->where('is_active', true)
                                        ->orderBy('sort_order');
                                },
                            ]);
                    },
                ])
                ->orderBy('sort_order')
                ->get()
                ->filter(fn (Module $module): bool => $this->userCanAccessNavigationNode($module));

            return $modules
                ->map(fn (Module $module): array => $this->transformModule($module, $locale, $shouldLoadApplications))
                ->values()
                ->all();
        });
    }

    /**
     * Locate an Application inside the permission-filtered navigation tree,
     * returning it together with its owning SubModule and Module. Page shells
     * use this to render breadcrumbs/headers for their own Application.
     *
     * Walks the tree rather than consulting a flat index: the tree is already
     * memoized per request and holds tens of nodes, so the scan is free while
     * a second cached index would need its own invalidation path.
     *
     * @return array{application: array<string, mixed>, subModule: array<string, mixed>, module: array<string, mixed>}|null
     */
    public function locateApplication(string $code): ?array
    {
        foreach ($this->getTreeForUser() as $module) {
            foreach ($module['sub_modules'] ?? [] as $subModule) {
                foreach ($subModule['applications'] ?? [] as $application) {
                    if (($application['code'] ?? null) === $code) {
                        return [
                            'application' => $application,
                            'subModule' => $subModule,
                            'module' => $module,
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Single Application lookup by code, backed by the same forever-cached,
     * version-invalidated store as the navigation tree. Shared across all
     * users (no permission filtering baked in) since it returns the raw
     * model — callers apply their own access checks.
     *
     * Rehydrates a fresh, unpersisted model from cached plain-array
     * attributes on every call. The cached payload is a plain array, never
     * a Collection or model: config('cache.serializable_classes') is false
     * in this app, so the cache unserializes with allowed_classes = false
     * and silently turns ANY cached object (including a bare Collection)
     * into __PHP_Incomplete_Class. Arrays serialize as `a:` (no class
     * involved) and are unaffected — same reason getTreeForUser() below
     * caches transformed arrays, not models.
     */
    public function getApplicationByCode(string $code): ?Application
    {
        $attributes = $this->getApplicationAttributesByCode()[$code] ?? null;

        return $attributes ? (new Application)->forceFill($attributes) : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getApplicationAttributesByCode(): array
    {
        $cacheKey = 'navigation-tree:applications-by-code:v'.$this->cache->store()->get($this->versionKey(), 1);

        return $this->cacheRepository()->rememberForever($cacheKey, fn (): array => Application::query()
            ->select(['id', 'submodule_id', 'code', 'name', 'icon', 'color', 'is_active', 'permission_name'])
            ->get()
            ->map(fn (Application $application): array => $application->getAttributes())
            ->keyBy('code')
            ->all()
        );
    }

    public function invalidateCache(): void
    {
        $this->cachedTree = null;

        $store = $this->cache->store()->getStore();

        if ($store instanceof TaggableStore) {
            $this->cache->store()->tags($this->cacheTags())->flush();

            return;
        }

        $this->cache->store()->add($this->versionKey(), 1);
        $this->cache->store()->increment($this->versionKey());
    }

    protected function transformModule(Module $module, string $locale, bool $shouldLoadApplications): array
    {
        return [
            'id' => $module->id,
            'name' => $this->translateAttribute($module, 'name', $locale),
            'code' => $module->code,
            'icon' => $module->icon,
            'route_name' => $module->route,
            'route' => $this->resolveRoute($module->route),
            'description' => $this->translateAttribute($module, 'description', $locale),
            'is_active' => (bool) $module->is_active,
            'sort_order' => (int) $module->sort_order,
            'sub_modules' => $module->subModules
                ->filter(fn (SubModule $subModule): bool => $this->userCanAccessNavigationNode($subModule))
                ->map(fn (SubModule $subModule): array => $this->transformSubModule($subModule, $locale, $shouldLoadApplications))
                ->values()
                ->all(),
        ];
    }

    protected function transformSubModule(SubModule $subModule, string $locale, bool $shouldLoadApplications): array
    {
        $applications = [];

        if ($shouldLoadApplications && $subModule->relationLoaded('applications')) {
            $applications = $subModule->applications
                ->filter(fn ($application): bool => $this->userCanAccessNavigationNode($application))
                ->map(fn ($application): array => $this->transformApplication($application, $locale))
                ->values()
                ->all();
        }

        return [
            'id' => $subModule->id,
            'name' => $this->translateAttribute($subModule, 'name', $locale),
            'code' => $subModule->code,
            'icon' => $subModule->icon,
            'route_name' => $subModule->route,
            'route' => $this->resolveRoute($subModule->route),
            'description' => $this->translateAttribute($subModule, 'description', $locale),
            'is_active' => (bool) $subModule->is_active,
            'sort_order' => (int) $subModule->sort_order,
            'applications' => $applications,
        ];
    }

    protected function transformApplication(object $application, string $locale): array
    {
        return [
            'id' => data_get($application, 'id'),
            'name' => $this->translateAttribute($application, 'name', $locale),
            'code' => data_get($application, 'code'),
            'icon' => data_get($application, 'icon'),
            'route_name' => data_get($application, 'route'),
            'route' => $this->resolveRoute(data_get($application, 'route')),
            'description' => $this->translateAttribute($application, 'description', $locale),
            'application_group' => $this->translateAttribute($application, 'application_group', $locale),
            'is_active' => (bool) data_get($application, 'is_active', false),
            'sort_order' => (int) data_get($application, 'sort_order', 0),
            'permission_name' => data_get($application, 'permission_name'),
        ];
    }

    protected function userCanAccessNavigationNode(object $application): bool
    {
        $permissionName = data_get($application, 'permission_name');

        if (blank($permissionName)) {
            return true;
        }

        $user = $this->auth->guard()->user();

        if (! $user) {
            return false;
        }

        try {
            return $user->can($permissionName);
        } catch (Throwable) {
            return false;
        }
    }

    protected function translateAttribute(object $model, string $attribute, string $locale): ?string
    {
        if (method_exists($model, 'getTranslation')) {
            try {
                return $model->getTranslation($attribute, $locale, useFallbackLocale: true);
            } catch (Throwable) {
                // Fall back to array/raw translation handling below.
            }
        }

        $value = data_get($model, $attribute);

        if (is_array($value)) {
            return $value[$locale] ?? $value[config('app.fallback_locale')] ?? reset($value) ?: null;
        }

        return is_string($value) ? $value : null;
    }

    protected function buildCacheKey(): string
    {
        $user = $this->auth->guard()->user();
        $locale = app()->getLocale();
        $tenantId = data_get($user, 'tenant_id', 'global');
        $userId = data_get($user, 'id', 'guest');
        $roleHash = $this->resolveRoleHash($user);
        $version = $this->cache->store()->get($this->versionKey(), 1);

        return implode(':', [
            'navigation-tree',
            'v'.$version,
            'tenant-'.$tenantId,
            'user-'.$userId,
            'locale-'.$locale,
            'roles-'.$roleHash,
        ]);
    }

    protected function resolveRoleHash(mixed $user): string
    {
        if (! $user) {
            return sha1('guest');
        }

        $roles = [];

        if (method_exists($user, 'getRoleNames')) {
            try {
                $roles = $user->getRoleNames()->all();
            } catch (Throwable) {
                $roles = [];
            }
        } elseif (method_exists($user, 'roles')) {
            try {
                $roles = $user->roles()->pluck('name')->all();
            } catch (Throwable) {
                $roles = [];
            }
        }

        sort($roles);

        $encodedRoles = json_encode($roles, JSON_UNESCAPED_UNICODE);

        return sha1($encodedRoles ?: '[]');
    }

    protected function versionKey(): string
    {
        return 'navigation-tree:version';
    }

    protected function cacheTags(): array
    {
        return ['navigation-tree'];
    }

    protected function cacheRepository(): mixed
    {
        $store = $this->cache->store()->getStore();

        if ($store instanceof TaggableStore) {
            return $this->cache->store()->tags($this->cacheTags());
        }

        return $this->cache->store();
    }

    /**
     * Resolve a stored route name to a URL, or null when the route cannot be linked.
     *
     * Module routes are registered behind a `{locale}` prefix, so the active locale
     * has to be supplied for those routes to be generated at all.
     */
    protected function resolveRoute(?string $routeName): ?string
    {
        if (blank($routeName)) {
            return null;
        }

        $route = Route::getRoutes()->getByName($routeName);

        if ($route === null) {
            return null;
        }

        $parameters = in_array('locale', $route->parameterNames(), true)
            ? ['locale' => app()->getLocale()]
            : [];

        try {
            return route($routeName, $parameters);
        } catch (Throwable) {
            return null;
        }
    }
}
