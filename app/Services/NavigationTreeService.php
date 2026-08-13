<?php

namespace App\Services;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\System\Module;
use Modules\General\System\SubModule;
use Throwable;

class NavigationTreeService
{
    protected ?array $cachedTree = null;

    public function __construct(
        protected CacheManager $cache,
        protected AuthFactory $auth,
    ) {
    }

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
                ])
                ->where('is_active', true)
                ->with([
                    'subModules' => function (Builder $query) use ($shouldLoadApplications): void {
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
                            ])
                            ->where('is_active', true)
                            ->orderBy('sort_order');

                        if ($shouldLoadApplications) {
                            $query->with([
                                'applications' => function (Builder $applicationQuery): void {
                                    $applicationQuery
                                        ->select([
                                            'id',
                                            'submodule_id',
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
                                        ->orderBy('sort_order');
                                },
                            ]);
                        }
                    },
                ])
                ->orderBy('sort_order')
                ->get();

            return $modules
                ->map(fn (Module $module): array => $this->transformModule($module, $locale, $shouldLoadApplications))
                ->all();
        });
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
            'route' => $module->route,
            'description' => $this->translateAttribute($module, 'description', $locale),
            'is_active' => (bool) $module->is_active,
            'sort_order' => (int) $module->sort_order,
            'sub_modules' => $module->subModules
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
                ->filter(fn ($application): bool => $this->userCanAccessApplication($application))
                ->map(fn ($application): array => $this->transformApplication($application, $locale))
                ->values()
                ->all();
        }

        return [
            'id' => $subModule->id,
            'name' => $this->translateAttribute($subModule, 'name', $locale),
            'code' => $subModule->code,
            'icon' => $subModule->icon,
            'route' => $subModule->route,
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
            'route' => data_get($application, 'route'),
            'description' => $this->translateAttribute($application, 'description', $locale),
            'is_active' => (bool) data_get($application, 'is_active', false),
            'sort_order' => (int) data_get($application, 'sort_order', 0),
            'permission_name' => data_get($application, 'permission_name'),
        ];
    }

    protected function userCanAccessApplication(object $application): bool
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
}
