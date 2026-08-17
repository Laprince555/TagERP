<div class="min-h-screen bg-[linear-gradient(180deg,color-mix(in_srgb,var(--color-primary)_8%,transparent),transparent_18%),var(--color-canvas-bg)]">
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-[2rem] border border-[var(--color-border)] bg-[var(--color-card-bg)]/92 p-6 shadow-xl shadow-[color:var(--color-primary)]/10 backdrop-blur sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-4">
                    <flux:avatar
                        circle
                        size="lg"
                        color="auto"
                        :name="$user?->name"
                        :color:seed="$user?->email ?? $user?->name"
                    />

                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-[var(--color-text-main)]/45">Profile</p>
                        <h1 class="mt-2 text-3xl font-black tracking-[-0.03em] text-[var(--color-text-main)]">
                            {{ $user?->name ?? 'Workspace User' }}
                        </h1>
                        <p class="mt-1 text-sm text-[var(--color-text-main)]/58">
                            {{ $user?->email ?? 'No email available' }}
                        </p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <flux:button wire:navigate href="{{ route('settings') }}" variant="primary" icon="cog-6-tooth">
                        Settings
                    </flux:button>
                    <flux:button wire:navigate href="{{ route('launcher') }}" variant="ghost" icon="squares-2x2">
                        Launcher
                    </flux:button>
                </div>
            </div>

            <div class="mt-8 grid gap-4 lg:grid-cols-3">
                <x-account-stat-card
                    label="Role"
                    :value="$user?->getRoleNames()->first() ?? 'Team member'"
                />

                <x-account-stat-card
                    label="Theme"
                    :value="str((string) ($user?->theme ?? 'orange-onyx'))->replace('-', ' ')"
                    class="capitalize"
                />

                <x-account-stat-card
                    label="Language"
                    :value="strtoupper((string) ($user?->locale ?? app()->getLocale()))"
                />

                <x-account-stat-card
                    label="Security"
                    :value="config('fortify.features') !== [] ? 'Fortify Enabled' : 'Basic Auth'"
                />
            </div>

            <div class="mt-8 rounded-[1.75rem] border border-dashed border-[var(--color-border)] bg-[var(--color-canvas-bg)]/72 p-6">
                <h2 class="text-xl font-black tracking-[-0.02em] text-[var(--color-text-main)]">Profile management</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-[var(--color-text-main)]/62">
                    Fortify profile features are enabled in the backend, and this page is now available as the destination for the user menu. We can wire inline profile editing here next if you want the full account-management flow on this screen.
                </p>
            </div>
        </div>
    </section>
</div>
