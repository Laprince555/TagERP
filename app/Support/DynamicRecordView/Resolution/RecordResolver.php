<?php

namespace App\Support\DynamicRecordView\Resolution;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the record for a DynamicRecordView through its own authorized
 * query() — never a bare Model::findOrFail(). Missing rows and rows excluded
 * by the developer's query() scopes (soft deletes, tenant scoping, explicit
 * authorization where()s) both fail as a plain 404, so a tampered ID can
 * never distinguish "doesn't exist" from "exists but you can't see it".
 */
class RecordResolver
{
    /** @var array<string, Model> memoized per request, keyed by view class + record id */
    protected array $resolved = [];

    public function resolve(DynamicRecordView $view, int|string $id): Model
    {
        $cacheKey = $view::class.'::'.$id;

        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }

        $query = $view->query();

        if ($eagerLoads = $view->requiredEagerLoads()) {
            $query->with($eagerLoads);
        }

        $model = $query->find($id);

        abort_if($model === null, 404);

        return $this->resolved[$cacheKey] = $model;
    }

    /**
     * Drops the memoized resolution (if any) for $view/$id, then resolves it
     * fresh. In production, each Livewire action is its own HTTP request with
     * a fresh scoped container, so memoization never survives between an
     * initial render and a later user action anyway. Livewire component
     * *action* entry points (e.g. setActiveTab) call this explicitly instead
     * of resolve() so a record deleted or made unauthorized after mount is
     * caught on the very next action even when the resolver instance is
     * incidentally shared across calls, as it is in Livewire::test().
     */
    public function resolveFresh(DynamicRecordView $view, int|string $id): Model
    {
        unset($this->resolved[$view::class.'::'.$id]);

        return $this->resolve($view, $id);
    }
}
