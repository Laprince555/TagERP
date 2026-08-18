<?php

namespace Modules\Finance\Livewire\CashAndBanks\Collections;

use App\Livewire\DynamicRecordView\RecordView;
use Livewire\Attributes\Layout;

#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class CollectionRecordView extends RecordView
{
    public function recordViewKey(): string
    {
        return 'finance.cash-and-banks.collection';
    }

    public function boot(): void
    {
        $this->applicationAccessible();
    }
}
