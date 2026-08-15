<div class="min-h-screen bg-[linear-gradient(180deg,color-mix(in_srgb,var(--color-primary)_6%,transparent),transparent_18%),var(--color-canvas-bg)]">
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-[2rem] border border-[var(--color-border)] bg-[var(--color-card-bg)]/92 p-6 shadow-xl shadow-[color:var(--color-primary)]/10 backdrop-blur sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-[var(--color-text-main)]/45">Settings</p>
                    <h1 class="mt-2 text-3xl font-black tracking-[-0.03em] text-[var(--color-text-main)]">Workspace Settings</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-[var(--color-text-main)]/62">
                        Central place for personal preferences, security options, and the next set of account controls.
                    </p>
                </div>

                <flux:button href="{{ route('profile') }}" variant="ghost" icon="user">
                    Back to Profile
                </flux:button>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-2">
                <x-feature-icon-card
                    icon="paint-brush"
                    title="Appearance"
                    description="Theme switching is available from the header dropdown."
                />

                <x-feature-icon-card
                    icon="shield-check"
                    title="Security"
                    description="Password and two-factor features are enabled through Fortify."
                    icon-class="bg-ledger-in-subtle text-ledger-in"
                />
            </div>

            <div class="mt-8 rounded-[1.75rem] border border-[var(--color-border)] bg-[var(--color-canvas-bg)]/78 p-6">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--color-text-main)]/45">Language</p>
                        <h2 class="mt-2 text-xl font-black text-[var(--color-text-main)]">Preferred interface language</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-7 text-[var(--color-text-main)]/58">
                            Choose the default language for your account. This preference is stored on your user record and mirrored into the current session.
                        </p>
                    </div>

                    <div class="flex w-full max-w-md flex-col gap-4">
                        <flux:select wire:model="locale" class="w-full">
                            @foreach ($availableLocales as $code => $meta)
                                <flux:select.option value="{{ $code }}">{{ $meta['native'] }} - {{ $meta['label'] }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <div class="flex items-center gap-3">
                            <flux:button wire:click="saveLocalePreference" variant="primary" icon="language">
                                Save Language
                            </flux:button>

                            @if ($statusMessage)
                                <p class="text-sm font-medium text-ledger-in">{{ $statusMessage }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 rounded-[1.75rem] border border-dashed border-[var(--color-border)] bg-[var(--color-canvas-bg)]/72 p-6">
                <h2 class="text-xl font-black tracking-[-0.02em] text-[var(--color-text-main)]">Next step</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--color-text-main)]/62">
                    This page now gives the user dropdown a real destination. If you want, we can follow up by adding editable profile information, password update forms, and two-factor management directly here using the Fortify actions already configured in the backend.
                </p>
            </div>
        </div>
    </section>
</div>
