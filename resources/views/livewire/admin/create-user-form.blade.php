<form wire:submit="submit" class="space-y-6">
    @if ($successMessage)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ $successMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <flux:field>
                <flux:label for="name">{{ __('Name') }}</flux:label>
                <flux:input id="name" type="text" wire:model="name" autocomplete="name" />
                <flux:error name="name" />
            </flux:field>
        </div>

        <div class="sm:col-span-2">
            <flux:field>
                <flux:label for="email">{{ __('Email Address') }}</flux:label>
                <flux:input id="email" type="email" wire:model="email" autocomplete="username" />
                <flux:error name="email" />
            </flux:field>
        </div>

        <div class="sm:col-span-2">
            <flux:field>
                <flux:label for="role">{{ __('Role') }}</flux:label>
                <flux:select id="role" wire:model="role">
                    <flux:option value="">{{ __('Select a role') }}</flux:option>
                    @foreach ($roles as $availableRole)
                        <flux:option value="{{ $availableRole }}">{{ str($availableRole)->headline() }}</flux:option>
                    @endforeach
                </flux:select>
                <flux:error name="role" />
            </flux:field>
        </div>

        <div>
            <flux:field>
                <flux:label for="password">{{ __('Password') }}</flux:label>
                <flux:input id="password" type="password" wire:model="password" autocomplete="new-password" />
                <flux:error name="password" />
            </flux:field>
        </div>

        <div>
            <flux:field>
                <flux:label for="password_confirmation">{{ __('Confirm Password') }}</flux:label>
                <flux:input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" />
            </flux:field>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-[var(--color-border)] pt-5">
        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
            <span wire:loading.remove>{{ __('Create User') }}</span>
            <span wire:loading>{{ __('Creating...') }}</span>
        </flux:button>
    </div>
</form>
