<?php

namespace Modules\General\Livewire\System\Backups;

use App\Livewire\DynamicRecordView\RecordView;
use App\Models\BackupLog;
use App\Services\NavigationTreeService;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class BackupRecordView extends RecordView
{
    public function boot(): void
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(BackupLog::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'general.system.backup';
    }
}
