<?php

namespace App\Support\DynamicRecordView\Core;

use App\Support\DynamicRecordView\Core\Exceptions\DuplicateRecordViewKeyException;
use App\Support\DynamicRecordView\Core\Exceptions\UnknownRecordViewKeyException;

/**
 * Trusted server-side key -> DynamicRecordView class mapping. Livewire
 * components must only ever carry a registry key as public state (never a
 * raw class name) so browser-supplied state can never instantiate an
 * arbitrary class — see App\Livewire\DynamicRecordView\{RecordView,OtherData}.
 *
 * Bound as a singleton (see AppServiceProvider), mirroring
 * App\Support\RecordReference\RecordReferenceRegistry. Modules register their
 * own views from their own ServiceProvider::boot().
 */
class RecordViewRegistry
{
    /** @var array<string, class-string<DynamicRecordView>> */
    protected array $views = [];

    /**
     * @param  class-string<DynamicRecordView>  $viewClass
     */
    public function register(string $key, string $viewClass): void
    {
        if (isset($this->views[$key])) {
            throw DuplicateRecordViewKeyException::forKey($key);
        }

        $this->views[$key] = $viewClass;
    }

    public function has(string $key): bool
    {
        return isset($this->views[$key]);
    }

    /**
     * @return class-string<DynamicRecordView>
     */
    public function resolve(string $key): string
    {
        return $this->views[$key] ?? throw UnknownRecordViewKeyException::forKey($key);
    }
}
