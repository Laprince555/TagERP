<?php

namespace App\Livewire\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class ThemeSwitcher extends Component
{
    public string $currentTheme = 'orange-onyx';

    /**
     * @var array<int, string>
     */
    public array $availableThemes = [
        'orange-onyx',
        'navy-blue',
        'emerald-dark',
    ];

    public function mount(): void
    {
        $this->currentTheme = auth()->user()?->theme ?: session('theme', 'orange-onyx');
    }

    public function switchTheme(string $theme): void
    {
        if (! in_array($theme, $this->availableThemes, true)) {
            return;
        }

        $this->currentTheme = $theme;
        session()->put('theme', $theme);

        if (auth()->check()) {
            auth()->user()->forceFill([
                'theme' => $theme,
            ])->save();
        }

        $this->dispatch('theme-changed', theme: $theme, themeClass: 'theme-'.$theme);
    }

    public function render(): View
    {
        return view('livewire.components.theme-switcher');
    }
}
