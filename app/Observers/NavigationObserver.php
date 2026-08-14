<?php

namespace App\Observers;

use App\Services\NavigationTreeService;
use App\Support\ModuleRoute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NavigationObserver
{
    public function __construct(protected NavigationTreeService $navigationTreeService) {}

    public function saved(Model $model): void
    {
        $this->invalidateWithLogging('save');
    }

    public function deleted(Model $model): void
    {
        $this->invalidateWithLogging('delete');
    }

    public function restored(Model $model): void
    {
        $this->invalidateWithLogging('restore');
    }

    private function invalidateWithLogging(string $action): void
    {
        try {
            $this->navigationTreeService->invalidateCache();
            ModuleRoute::forgetSubModuleRoutes();
        } catch (\Exception $e) {
            Log::warning('Navigation cache invalidation failed on '.$action.': '.$e->getMessage());
        }
    }
}
