@props(['title', 'subtitle' => null, 'code' => null, 'record' => null, 'actions' => []])

<div class="flex flex-col gap-2 rounded-[1.75rem] border border-[var(--color-glass-border)] bg-[var(--color-surface-1)] p-5 sm:p-6 shadow-lg shadow-[var(--color-card-shadow)]">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex flex-col gap-2">
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl" level="1">{{ $title }}</flux:heading>

                @if ($code)
                    <flux:badge color="zinc" size="sm">{{ $code }}</flux:badge>
                @endif
            </div>

            @if ($subtitle)
                <p class="text-sm text-[var(--color-soft-text)]">{{ $subtitle }}</p>
            @endif
        </div>

        @if ($actions !== [])
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($actions as $action)
                    @php($url = $action->isLink() ? $action->getUrl($record) : null)
                    @continue($action->isLink() && ! $url)

                    @if ($action->isLink())
                        <flux:button
                            wire:key="record-action-{{ $action->getKey() }}"
                            :href="$url"
                            wire:navigate
                            size="sm"
                            :variant="$action->getVariant()"
                            :icon="$action->getIcon()"
                        >{{ $action->getLabel() }}</flux:button>
                    @elseif ($action->isForm())
                        <flux:button
                            wire:key="record-action-{{ $action->getKey() }}"
                            type="button"
                            size="sm"
                            :variant="$action->getVariant()"
                            :icon="$action->getIcon()"
                            x-on:click="$dispatch('open-form-modal.{{ $action->getFormModalKey() }}')"
                        >{{ $action->getLabel() }}</flux:button>
                    @else
                        <flux:button
                            wire:key="record-action-{{ $action->getKey() }}"
                            type="button"
                            size="sm"
                            :variant="$action->getVariant()"
                            :icon="$action->getIcon()"
                            wire:click="runAction('{{ $action->getKey() }}')"
                            wire:loading.attr="disabled"
                            :wire:confirm="$action->getConfirm()"
                        >{{ $action->getLabel() }}</flux:button>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{ $slot ?? '' }}
</div>
