<?php

namespace App\Support\RecordReference;

use InvalidArgumentException;

/**
 * Immutable-after-boot Application code -> provider mapping. Modules
 * register their own providers from their ServiceProvider::boot(); nothing
 * outside a module ever names its concrete provider/model classes.
 *
 * Bound as a singleton (see AppServiceProvider), so registration happens
 * once per request during the framework boot sequence.
 */
class RecordReferenceRegistry
{
    /** @var array<string, RecordReferenceProvider> */
    protected array $providers = [];

    public function register(RecordReferenceProvider $provider): void
    {
        $code = $provider->applicationCode();

        if (isset($this->providers[$code])) {
            throw new InvalidArgumentException("A record reference provider is already registered for Application code [{$code}].");
        }

        $this->providers[$code] = $provider;
    }

    public function has(string $applicationCode): bool
    {
        return isset($this->providers[$applicationCode]);
    }

    public function resolve(string $applicationCode): ?RecordReferenceProvider
    {
        return $this->providers[$applicationCode] ?? null;
    }
}
