<?php

use App\Livewire\Components\LanguageSwitcher;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('renders the shared chrome once, persisted across wire:navigate visits', function (): void {
    $this->get(route('launcher'))
        ->assertSuccessful()
        ->assertSee('x-persist="app-header"', false)
        ->assertSee('x-persist="app-overlays"', false)
        ->assertSee('data-current-route="launcher"', false);
});

it('navigates between pages without a full page load', function (): void {
    $this->get(route('launcher'))
        ->assertSuccessful()
        ->assertSee('wire:navigate', false);
});

it('reloads the whole document when the locale changes so <html lang> and the persisted header follow', function (): void {
    Livewire::test(LanguageSwitcher::class)
        ->call('switchLocale', 'ar')
        ->assertRedirect();

    expect(Livewire::test(LanguageSwitcher::class)->call('switchLocale', 'ar')->effects['redirectUsingNavigate'] ?? false)
        ->toBeFalse();
});
