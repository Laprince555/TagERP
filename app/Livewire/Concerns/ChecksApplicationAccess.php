<?php

namespace App\Livewire\Concerns;

use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ChecksApplicationAccess
{
    public function boot(): void
    {
        $this->checkAccess();
    }

    public function hydrate(): void
    {
        $this->checkAccess();
    }

    protected function checkAccess(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode($this->applicationCode());

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    abstract protected function applicationCode(): string;
}
