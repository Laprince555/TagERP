<?php

namespace App\Livewire\RecordReference;

use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferencePreview;
use App\Support\RecordReference\RecordReferenceRegistry;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Modules\General\System\Application;

/**
 * Exactly one instance per page (mounted once in the app layout). Resolves
 * provider + model exclusively through the server registry, authorizes
 * before returning anything, and queries only provider-declared columns.
 * Never returns a full model — always the minimal array shape below.
 *
 * Client-side caching/dedup/hover-delay live in Alpine (see
 * resources/views/livewire/record-reference/preview-host.blade.php); this
 * component only ever answers one authorized lookup per call.
 */
class PreviewHost extends Component
{
    /**
     * @return array{available: bool, status: ?string, title: ?string, url: ?string, facts: array<int, array{label: string, value: mixed}>}
     */
    public function loadPreview(string $applicationCode, string $recordKey): array
    {
        // Allowlisted scalar-only input: applicationCode/recordKey are
        // plain strings, never a class/route/column/SQL expression.
        if ($applicationCode === '' || $recordKey === '' || mb_strlen($applicationCode) > 64 || mb_strlen($recordKey) > 64) {
            return RecordReferencePreview::unavailable()->toArray();
        }

        // Authenticated users only: this is an authenticated ERP surface.
        if (! auth()->check()) {
            return RecordReferencePreview::unavailable()->toArray();
        }

        // Bounded per-user preview rate guard (ponytail: fixed window via
        // Laravel's RateLimiter, not a bespoke token bucket).
        $rateKey = 'record-reference-preview:'.auth()->id();
        if (RateLimiter::tooManyAttempts($rateKey, 60)) {
            return RecordReferencePreview::unavailable()->toArray();
        }
        RateLimiter::hit($rateKey, 60);

        $provider = app(RecordReferenceRegistry::class)->resolve($applicationCode);

        if (! $provider) {
            return RecordReferencePreview::unavailable()->toArray();
        }

        $access = app(RecordReferenceAccess::class);
        $application = Application::query()
            ->select(['id', 'code', 'name', 'icon', 'color', 'is_active', 'permission_name'])
            ->where('code', $applicationCode)
            ->first();

        if (! $access->applicationAccessible($application)) {
            return RecordReferencePreview::unavailable()->toArray();
        }

        $modelClass = $provider->modelClass();
        $columns = array_values(array_unique(array_merge(['id'], $provider->identityColumns(), $provider->previewColumns())));

        $record = $provider->scopeQuery($modelClass::query())
            ->select($columns)
            ->find($recordKey);

        if (! $access->recordAccessible($provider, $application, $record)) {
            // Generic unavailable state — never distinguishes "not found" from "forbidden".
            return RecordReferencePreview::unavailable()->toArray();
        }

        return (new RecordReferencePreview(
            available: true,
            status: null,
            title: $provider->title($record),
            url: $provider->url($record),
            facts: array_map(
                fn ($fact) => ['label' => $fact->label, 'value' => $fact->value],
                $provider->previewFacts($record),
            )
        ))->toArray();
    }

    public function render()
    {
        $contextToken = md5(implode(':', [
            app()->getLocale(),
            auth()->id() ?: 'guest',
        ]));

        return view('livewire.record-reference.preview-host', [
            'contextToken' => $contextToken,
        ]);
    }
}
