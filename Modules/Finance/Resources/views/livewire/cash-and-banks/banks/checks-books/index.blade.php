<div class="min-h-screen">
    {{-- ponytail: no application header — ChecksBooksIndex::render() doesn't pass application/subModule/module context yet; add both together when that's wired --}}
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <livewire:finance.checks-books-table />
    </section>
</div>
