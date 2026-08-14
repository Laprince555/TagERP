<?php

namespace Modules\General\Livewire\World\Countries;

use App\Livewire\DynamicRecordView\RecordView;
use App\Support\RecordReference\RecordReferenceAccess;
use Livewire\Attributes\Layout;
use Modules\General\System\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reachable page for the Country show route (general.world.countries.show).
 * Thin adapter only — behavior lives in App\Livewire\DynamicRecordView\RecordView.
 */
#[Layout('layouts.app')]
class CountryRecordView extends RecordView
{
    public function boot(): void
    {
        $application = Application::query()->where('code', 'gen-wld-ctr')->first();

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            throw new NotFoundHttpException;
        }
    }

    protected function recordViewKey(): string
    {
        return 'general.world.country';
    }
}
