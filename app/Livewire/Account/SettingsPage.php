<?php

namespace App\Livewire\Account;

use App\Support\LocaleOptions;
use App\Support\UserPreferenceState;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingsPage extends Component
{
    public string $locale = 'en';

    /**
     * @var array<string, array{label: string, native: string}>
     */
    public array $availableLocales = [];

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->availableLocales = LocaleOptions::available();
        $this->locale = UserPreferenceState::locale(auth()->user());
    }

    public function saveLocalePreference(): void
    {
        if (! LocaleOptions::isSupported($this->locale)) {
            return;
        }

        app()->setLocale($this->locale);
        UserPreferenceState::persistLocale($this->locale, auth()->user());

        $this->statusMessage = 'Language preference updated successfully.';
    }

    public function render(): View
    {
        return view('livewire.account.settings-page', [
            'user' => auth()->user(),
        ]);
    }
}
