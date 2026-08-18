<?php

namespace Modules\CRM\Livewire\Customers;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\CRM\Models\Customers\Customer;

/**
 * Page shell for the seeded Application route. The table itself lives in
 * CustomersTable.
 */
#[Layout('layouts.app')]
class CustomersIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return Customer::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(Customer::APPLICATION_CODE);

        return view('crm::livewire.customers.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
