<?php

namespace App\Livewire\Account;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProfilePage extends Component
{
    public function render(): View
    {
        return view('livewire.account.profile-page', [
            'user' => auth()->user(),
        ]);
    }
}
