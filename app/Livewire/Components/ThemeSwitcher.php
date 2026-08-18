<?php

namespace App\Livewire\Components;

use App\Support\UserPreferenceState;
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
        'palestine',
        'forest-acid',
        'violet-mist',
        'inkberry-peach',
    ];

    public function mount(): void
    {
        $this->currentTheme = UserPreferenceState::theme(auth()->user());
    }

    public function switchTheme(string $theme): void
    {
        if (! in_array($theme, $this->availableThemes, true)) {
            return;
        }

        $this->currentTheme = $theme;
        UserPreferenceState::persistTheme($theme, auth()->user());

        $this->dispatch('theme-changed', theme: $theme, themeClass: 'theme-'.$theme);
    }

    public function render(): View
    {
        return view('livewire.components.theme-switcher');
    }
}
