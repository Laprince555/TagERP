<?php

namespace App\Livewire\Components;

use App\Support\LocaleOptions;
use App\Support\UserPreferenceState;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLocale = 'en';

    /**
     * @var array<string, array{label: string, native: string}>
     */
    public array $availableLocales = [];

    public function mount(): void
    {
        $this->availableLocales = LocaleOptions::available();
        $this->currentLocale = UserPreferenceState::locale(auth()->user());
    }

    public function switchLocale(string $locale): void
    {
        if (! LocaleOptions::isSupported($locale)) {
            return;
        }

        $this->currentLocale = $locale;

        app()->setLocale($locale);
        UserPreferenceState::persistLocale($locale, auth()->user());

        /** Full page load on purpose: <html lang>/<dir> and the persisted header are not swapped by wire:navigate. */
        $this->redirect(url()->previous() ?: route('launcher'));
    }

    public function render(): View
    {
        return view('livewire.components.language-switcher');
    }
}
