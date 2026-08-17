@props(['tableKey', 'templateColumns' => []])

{{--
    Hosted by the table component itself rather than its own Livewire component:
    the upload property, the queued job and the table's own identity all live on
    the table, so a separate component would only exist to hand them back.
--}}
<div
    x-data
    x-init="$wire.on('import-queued', () => $flux.modal('import-{{ $tableKey }}').close())"
>
    <flux:modal name="import-{{ $tableKey }}" class="w-full max-w-lg">
        <form wire:submit="startImport" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Import from a spreadsheet') }}</flux:heading>
                <flux:subheading>{{ __('Upload a .csv or .xlsx file built from the template below.') }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Attach file') }}</flux:label>
                <flux:input type="file" wire:model="importFile" accept=".csv,.xlsx" />
                <flux:error name="importFile" />
            </flux:field>

            <div
                x-data="{ progress: 0, uploading: false }"
                x-on:livewire-upload-start="uploading = true"
                x-on:livewire-upload-finish="uploading = false"
                x-on:livewire-upload-error="uploading = false"
                x-on:livewire-upload-progress="progress = $event.detail.progress"
                x-show="uploading"
                x-cloak
            >
                <flux:text size="sm" x-text="`{{ __('Uploading') }} ${progress}%`"></flux:text>
            </div>

            @if ($templateColumns !== [])
                <flux:callout icon="table-cells" variant="secondary">
                    <flux:callout.heading>{{ __('Template columns') }}</flux:callout.heading>
                    <flux:callout.text>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            @foreach ($templateColumns as $column)
                                <flux:badge size="sm" wire:key="import-column-{{ $tableKey }}-{{ $column['key'] }}">
                                    {{ $column['label'] }}{{ $column['required'] ? ' *' : '' }}
                                </flux:badge>
                            @endforeach
                        </div>
                    </flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex items-center justify-between gap-3">
                <flux:button size="sm" variant="ghost" icon="arrow-down-tray" wire:click="downloadTemplate" type="button">
                    {{ __('Download template') }}
                </flux:button>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="startImport,importFile">
                    {{ __('Start import') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
