<flux:breadcrumbs aria-label="{{ __('messages.workspace.breadcrumb') }}" class="flex-wrap gap-y-1">
    <flux:breadcrumbs.item :href="url('/')" icon="home" />

    @foreach ($breadcrumbs as $breadcrumb)
        @if ($loop->last)
            <flux:breadcrumbs.item aria-current="page">{{ $breadcrumb['label'] }}</flux:breadcrumbs.item>
        @elseif (! empty($breadcrumb['url']))
            <flux:breadcrumbs.item :href="$breadcrumb['url']">{{ $breadcrumb['label'] }}</flux:breadcrumbs.item>
        @else
            <flux:breadcrumbs.item>{{ $breadcrumb['label'] }}</flux:breadcrumbs.item>
        @endif
    @endforeach
</flux:breadcrumbs>
