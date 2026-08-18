<?php

namespace Modules\Finance\Livewire\CashAndBanks\PaymentDisbursements;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\CashAndBanks\PaymentDisburse\PaymentDisbursementRequest;

#[Layout('layouts.app')]
class PaymentDisbursementsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return PaymentDisbursementRequest::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(PaymentDisbursementRequest::APPLICATION_CODE);

        return view('finance::livewire.cash-and-banks.payment-disbursements.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
