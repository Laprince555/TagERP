<?php

namespace App\Support\RecordReference;

use App\Services\NavigationTreeService;
use Illuminate\Support\Collection;
use Modules\General\System\Application;
use Throwable;

/**
 * The one query-free access boundary shared by table rendering, the
 * preview host, and the Country show route. applications() reads the
 * NavigationTreeService forever-cache (invalidated by NavigationObserver on
 * any Module/SubModule/Application write) — no DB query on the render path.
 */
class RecordReferenceAccess
{
    public function __construct(protected NavigationTreeService $navigationTreeService) {}

    /**
     * Every distinct Application referenced by the current render, resolved
     * from the shared navigation cache instead of a per-render DB query.
     *
     * @param  string[]  $codes
     * @return Collection<string, Application>
     */
    public function applications(array $codes): Collection
    {
        $codes = array_values(array_unique(array_filter($codes)));

        if ($codes === []) {
            return collect();
        }

        return collect($codes)
            ->map(fn (string $code): ?Application => $this->navigationTreeService->getApplicationByCode($code))
            ->filter()
            ->keyBy('code');
    }

    /**
     * Application-level access only (no record loaded yet): authenticated
     * actor, active Application, permission_name when declared. Fails
     * closed on any permission-check error.
     */
    public function applicationAccessible(?Application $application): bool
    {
        if (! $application || ! $application->is_active) {
            return false;
        }

        if (! auth()->check()) {
            return false;
        }

        if (! empty($application->permission_name)) {
            try {
                return (bool) auth()->user()?->can($application->permission_name);
            } catch (Throwable) {
                return false;
            }
        }

        return true;
    }

    /**
     * Full access to one already-loaded record: application-level access
     * plus the provider's own pure, query-free record-level rule.
     */
    public function recordAccessible(RecordReferenceProvider $provider, ?Application $application, $record): bool
    {
        return $this->applicationAccessible($application) && $record !== null && $provider->authorize($record);
    }
}
