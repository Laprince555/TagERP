# Record Resolution

`App\Support\DynamicRecordView\Resolution\RecordResolver::resolve($view, $id)`
is the only way a `DynamicRecordView`'s record is ever loaded:

```php
$model = $view->query()->find($id);
abort_if($model === null, 404);
```

- **Never** `Model::findOrFail()` — always through the developer's own
  `query()`, so any scope, soft-delete exclusion, or explicit authorization
  `where()` baked into `query()` is respected.
- A missing row and a row excluded by `query()` (i.e. "exists but you're not
  authorized") both 404 identically — a tampered id can't distinguish the two.
- **Memoized per request**, keyed by `$view::class.'::'.$id`, bound as
  `$this->app->scoped(RecordResolver::class)` in `AppServiceProvider` — so
  `mount()` and `render()` calling `resolveRecord()` multiple times in one
  Livewire request never re-query.

`App\Livewire\DynamicRecordView\RecordView::resolveRecord()` and
`OtherData::resolveRecord()` are the adapters that call this from a Livewire
component. See `tests/Feature/DynamicRecordView/RecordResolutionTest.php` for
the security tests (tampered id, unauthorized query, memoization).

## `resolveFresh()` — action entry points

Per-request memoization means a record deleted or made unauthorized *after*
`mount()` would otherwise still resolve from cache on a later action within
the same request/instance. `RecordResolver::resolveFresh($view, $id)` drops
the memoized entry (if any) and resolves again, so it always reflects current
state. Every Livewire component action that (re)touches the record —
`RecordView::setActiveTab()` and `OtherData::setActiveTab()` — calls
`resolveFresh()`, not `resolve()`, so a parent deleted or unauthorized between
mount and the next action 404s on that action too, not just on the next
full page load. `OtherData::resolveRecord()` itself is also never cached on
the component (it re-resolves via the resolver on every call), matching
`RecordView`'s pattern, so authorization callbacks always receive the actual
current model, not a stale one keyed only off the raw id.

Note: because Livewire converts `HttpException` (including the
`NotFoundHttpException` `abort_if(...)` throws) into a normal 404 HTTP
response rather than letting it bubble up as a PHP exception (see
`Livewire\Features\SupportTesting\RequestBroker::temporarilyDisableExceptionHandlingAndMiddleware()`),
tests assert `->assertStatus(404)` on the Livewire test instance rather than
`expect(...)->toThrow(NotFoundHttpException::class)`.
