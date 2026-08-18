<?php

namespace Modules\Finance\Livewire\CashAndBanks\Banks\ChecksBooks;

use App\Livewire\DynamicRecordView\RecordView;
use Livewire\Attributes\Layout;

#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class ChecksBookRecordView extends RecordView
{
    protected function recordViewKey(): string
    {
        return 'finance.cash-and-banks.checks-book';
    }
}
