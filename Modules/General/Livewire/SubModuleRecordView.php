<?php

namespace Modules\General\Livewire;

use App\Livewire\DynamicRecordView\RecordView;
use Livewire\Attributes\Layout;

/**
 * Reachable page for SubModuleRecordView (see routes/web.php registration:
 * general.sub-modules.view). Purely a thin adapter — all record-view
 * behavior lives in App\Livewire\DynamicRecordView\RecordView.
 */
#[Layout('layouts.app')]
class SubModuleRecordView extends RecordView
{
    protected function recordViewKey(): string
    {
        return 'general.sub-module';
    }
}
