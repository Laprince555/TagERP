<?php

namespace Modules\Finance\Livewire\AccountsPayable\Deductions;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\AccountsPayable\Deduction;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * DeductionsTable.
 */
#[Layout('layouts.app')]
class DeductionsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Deduction::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Deduction::APPLICATION_CODE);

        return view('finance::livewire.accounts-payable.deductions.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
