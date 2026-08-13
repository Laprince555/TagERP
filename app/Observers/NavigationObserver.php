<?php

namespace App\Observers;

use App\Services\NavigationTreeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NavigationObserver
{
    public function __construct(protected NavigationTreeService $navigationTreeService)
    {
    }

    public function saved(Model $model): void
    {
        try {
            $this->navigationTreeService->invalidateCache();
        } catch (\Exception $e) {
            Log::warning('Navigation cache invalidation failed on save: ' . $e->getMessage());
        }
    }

    public function deleted(Model $model): void
    {
        try {
            $this->navigationTreeService->invalidateCache();
        } catch (\Exception $e) {
            Log::warning('Navigation cache invalidation failed on delete: ' . $e->getMessage());
        }
    }

    public function restored(Model $model): void
    {
        try {
            $this->navigationTreeService->invalidateCache();
        } catch (\Exception $e) {
            Log::warning('Navigation cache invalidation failed on restore: ' . $e->getMessage());
        }
    }
}
