<?php

namespace Modules\Finance\Livewire\CashAndBanks\Collections;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Services\NavigationTreeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Finance\Models\CashAndBanks\Collection\CollectionRequest;

#[Layout('layouts.app')]
class CollectionsIndex extends Component
{
    use ChecksApplicationAccess;

    protected function applicationCode(): string
    {
        return CollectionRequest::APPLICATION_CODE;
    }

    public function render(): View
    {
        $context = app(NavigationTreeService::class)->locateApplication(CollectionRequest::APPLICATION_CODE);

        return view('finance::livewire.cash-and-banks.collections.index', [
            'application' => $context['application'] ?? null,
            'subModule' => $context['subModule'] ?? null,
            'module' => $context['module'] ?? null,
        ]);
    }
}
