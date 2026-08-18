<?php

namespace Modules\Finance\Livewire\CashAndBanks\PaymentDisbursements;

use App\Livewire\DynamicRecordView\RecordView;
use Livewire\Attributes\Layout;

#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class PaymentDisbursementRecordView extends RecordView
{
    public function recordViewKey(): string
    {
        return 'finance.cash-and-banks.payment-disbursement';
    }

    public function boot(): void
    {
        $this->applicationAccessible();
    }
}
