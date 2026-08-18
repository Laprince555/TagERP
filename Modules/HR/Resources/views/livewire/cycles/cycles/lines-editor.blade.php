@php
    $cycle = $this->cycle();
    $editable = $this->isEditable();
@endphp

<div class="min-h-screen">
    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        <livewire:general.breadcrumbs :trailing="$cycle->name" />
    </div>

    <x-general::workspace.application-header
        :application="$application"
        :sub-module="$subModule"
        :module="$module"
    />

    <section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $cycle->name }}</flux:heading>
                <flux:text class="mt-1">{{ $cycle->code }}</flux:text>

                <flux:button
                    :href="route('hr.cycles.cycles.show', ['recordId' => $cycle->getKey()])"
                    wire:navigate
                    size="sm"
                    variant="ghost"
                    icon="arrow-left"
                    class="mt-3"
                >
                    {{ __('Back to cycle') }}
                </flux:button>
            </div>
        </div>

        @if ($flash)
            <flux:callout variant="success" icon="check-circle" class="mb-4" x-data x-init="setTimeout(() => $wire.set('flash', null), 4000)">
                {{ $flash }}
            </flux:callout>
        @endif

        @unless ($editable)
            <flux:callout variant="secondary" icon="lock-closed" class="mb-4">
                {{ __('You do not have permission to edit this cycle\'s stages.') }}
            </flux:callout>
        @endunless

        <flux:card class="overflow-x-auto">
            <table class="min-w-[64rem] w-full text-sm">
                <thead>
                    <tr class="text-left text-zinc-500">
                        <th class="w-12 py-2">#</th>
                        <th class="py-2">{{ __('Stage Name') }}</th>
                        <th class="py-2">{{ __('Job Title') }}</th>
                        <th class="py-2">{{ __('Job Grade') }}</th>
                        <th class="py-2">{{ __('On Approve') }}</th>
                        <th class="py-2">{{ __('On Reject') }}</th>
                        <th class="w-32 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr wire:key="cycle-line-row-{{ $row['id'] ?? 'new-'.$index }}" class="border-t border-zinc-200 dark:border-zinc-700">
                            <td class="py-2">{{ $row['sequence'] }}</td>
                            <td class="py-2">
                                <flux:input wire:model="rows.{{ $index }}.name" :disabled="! $editable" size="sm" />
                                <flux:error name="rows.{{ $index }}.name" />
                            </td>
                            <td class="py-2">
                                <flux:select wire:model="rows.{{ $index }}.job_title_id" :disabled="! $editable" size="sm">
                                    <flux:select.option value="">{{ __('Select…') }}</flux:select.option>
                                    @foreach ($this->jobTitleOptions() as $jobTitle)
                                        <flux:select.option value="{{ $jobTitle->id }}">{{ $jobTitle->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="rows.{{ $index }}.job_title_id" />
                            </td>
                            <td class="py-2">
                                <flux:select wire:model="rows.{{ $index }}.job_grade_id" :disabled="! $editable" size="sm">
                                    <flux:select.option value="">{{ __('Any grade') }}</flux:select.option>
                                    @foreach ($this->jobGradeOptions() as $jobGrade)
                                        <flux:select.option value="{{ $jobGrade->id }}">{{ $jobGrade->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </td>
                            <td class="py-2">
                                <flux:input wire:model="rows.{{ $index }}.target_status_on_approve" :disabled="! $editable" size="sm" placeholder="{{ __('unchanged') }}" />
                            </td>
                            <td class="py-2">
                                <flux:input wire:model="rows.{{ $index }}.target_status_on_reject" :disabled="! $editable" size="sm" />
                            </td>
                            <td class="py-2">
                                <div class="flex items-center gap-1">
                                    <flux:button wire:click="moveUp({{ $index }})" :disabled="! $editable" size="sm" variant="ghost" icon="chevron-up" />
                                    <flux:button wire:click="moveDown({{ $index }})" :disabled="! $editable" size="sm" variant="ghost" icon="chevron-down" />
                                    <flux:button wire:click="removeRow({{ $index }})" :disabled="! $editable" size="sm" variant="ghost" icon="trash" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4 flex items-center justify-between">
                <flux:button wire:click="addRow" :disabled="! $editable" size="sm" variant="ghost" icon="plus">
                    {{ __('Add stage') }}
                </flux:button>

                <flux:button wire:click="save" :disabled="! $editable" variant="primary">
                    {{ __('Save stages') }}
                </flux:button>
            </div>
        </flux:card>
    </section>
</div>
