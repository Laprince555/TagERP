<?php

namespace App\Livewire\General;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class Header extends Component
{
    public bool $showSidebar = true;

    #[Modelable]
    public string $search = '';

    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.general.header', [
            'userName' => $user?->name ?? '',
            'userRole' => $user?->getRoleNames()->first() ?? '',
        ]);
    }
}
