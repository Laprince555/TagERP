<?php

namespace Modules\Finance\Livewire\GeneralLedger\ExchangeRates;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * ExchangeRatesTable.
 */
#[Layout('layouts.app')]
class ExchangeRatesIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return ExchangeRate::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(ExchangeRate::APPLICATION_CODE);

        return view('finance::livewire.general-ledger.exchange-rates.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
