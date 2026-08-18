<?php

namespace Modules\Finance\Livewire\CashAndBanks\Banks\Accounts;

use App\Livewire\DynamicRecordView\RecordView;
use Livewire\Attributes\Layout;

#[Layout('layouts.app', ['showBreadcrumbs' => false])]
class BankAccountRecordView extends RecordView
{
    protected function recordViewKey(): string
    {
        return 'finance.cash-and-banks.bank-account';
    }
}
