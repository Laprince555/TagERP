<div class="min-h-screen">
    <x-general::workspace.application-header
        :application="$application"
        :sub-module="$subModule"
        :module="$module"
    />

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <livewire:finance.tax-categories-table />
    </section>
</div>
