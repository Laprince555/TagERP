<?php

namespace Modules\Finance\Livewire\CashAndBanks\Banks\Checks;

use App\Livewire\DynamicRecordView\RecordView;
use Livewire\Attributes\Layout;

#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class CheckRecordView extends RecordView
{
    protected function recordViewKey(): string
    {
        return 'finance.cash-and-banks.check';
    }
}
