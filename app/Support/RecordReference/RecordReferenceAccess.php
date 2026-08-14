<?php

namespace App\Support\RecordReference;

use Illuminate\Support\Collection;
use Modules\General\System\Application;
use Throwable;

/**
 * The one query-free* access boundary shared by table rendering, the
 * preview host, and the Country show route. (*applications() itself issues
 * exactly one bounded query — call it once per render, outside any loop,
 * never per row.)
 */
class RecordReferenceAccess
{
    /**
     * One query for every distinct Application code referenced by the
     * current render. Selects only what identity/authorization needs.
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

        return Application::query()
            ->select(['id', 'code', 'name', 'icon', 'color', 'is_active', 'permission_name'])
            ->whereIn('code', $codes)
            ->get()
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
