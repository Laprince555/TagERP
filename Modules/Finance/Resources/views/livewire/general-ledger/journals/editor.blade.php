@php
    $journal = $this->journal();
    $editable = $this->isEditable();
    $totals = $this->totals();
@endphp

<div class="min-h-screen">
    {{--
        First thing in the page, in the layout's own container — this screen
        opts out of the layout breadcrumb only to add the record's number to
        it, so it has to land in exactly the same place the layout would have
        put it. See layouts/app.blade.php.
    --}}
    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        <livewire:general.breadcrumbs :trailing="$journal->number ?? $journal->code" />
    </div>

    <x-general::workspace.application-header
        :application="$application"
        :sub-module="$subModule"
        :module="$module"
    />

    {{-- max-w-7xl like every other page: the line grid is min-w-[72rem] inside
         its own overflow-x-auto, so it still fits here and scrolls below it. --}}
    <section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Identity and status --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                {{--
                    Whose books this journal lands in, above the number rather
                    than among the badges on the right: someone keeping several
                    companies has to see it before they start keying, not after
                    scanning a badge row that may have wrapped off screen.
                --}}
                @if ($journal->entity)
                    <flux:text class="flex items-center gap-1.5 font-semibold">
                        <flux:icon name="building-office-2" variant="micro" />
                        {{ $journal->entity->name }}
                    </flux:text>
                @endif

                <flux:heading size="xl">{{ $journal->number ?? __('Unposted journal') }}</flux:heading>
                <flux:text class="mt-1">{{ $journal->code }}</flux:text>

                {{--
                    The way back to the document. This screen is only the line
                    grid; posting, reversing, editing the header and deleting
                    all live on the record view, so leaving without a link
                    would mean going through the list to get to any of them.
                --}}
                <flux:button
                    :href="route('finance.general-ledger.journals.show', ['recordId' => $journal->getKey()])"
                    wire:navigate
                    size="sm"
                    variant="ghost"
                    icon="arrow-left"
                    class="mt-3"
                >
                    {{ __('Back to journal') }}
                </flux:button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:badge
                    :color="match ($journal->status->value) {
                        'posted' => 'green',
                        'reversed' => 'amber',
                        default => 'zinc',
                    }"
                    size="lg"
                >
                    {{ $journal->status->label() }}
                </flux:badge>

                <flux:badge color="zinc" size="lg" icon="book-open">
                    {{ $journal->journalBook?->name }}
                </flux:badge>

                <flux:badge color="zinc" size="lg" icon="circle-stack">
                    {{ $journal->ledger?->name }}
                </flux:badge>

                <flux:badge color="zinc" size="lg" icon="calendar-days">
                    {{ $journal->fiscalPeriod?->name }}
                </flux:badge>
            </div>
        </div>

        @if ($flash)
            <flux:callout variant="success" icon="check-circle" class="mb-4" x-data x-init="setTimeout(() => $wire.set('flash', null), 4000)">
                {{ $flash }}
            </flux:callout>
        @endif

        @if ($postingError)
            <flux:callout variant="danger" icon="exclamation-triangle" class="mb-4">
                <flux:callout.heading>{{ __('This journal cannot be posted yet') }}</flux:callout.heading>
                <flux:callout.text>{{ $postingError }}</flux:callout.text>
            </flux:callout>
        @endif

        @if (! $editable)
            <flux:callout variant="secondary" icon="lock-closed" class="mb-4">
                {{ $journal->isGenerated()
                    ? __('This journal was generated from another ledger and is maintained there.')
                    : __('A posted journal is never edited. Reverse it and enter a corrected one.') }}
            </flux:callout>
        @endif

        {{-- Document header --}}
        <flux:card class="mb-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <flux:field>
                    <flux:label>{{ __('Date') }}</flux:label>
                    <flux:input type="date" wire:model="header.journal_date" :disabled="! $editable" />
                    <flux:error name="header.journal_date" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Source Reference') }}</flux:label>
                    <flux:input
                        wire:model="header.source_reference"
                        :disabled="! $editable"
                        placeholder="{{ __('e.g. PI-2026-0042') }}"
                    />
                    <flux:error name="header.source_reference" />
                </flux:field>

                <flux:field class="lg:col-span-2">
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:input wire:model="header.description" :disabled="! $editable" />
                    <flux:error name="header.description" />
                </flux:field>
            </div>
        </flux:card>

        {{--
            The grid. Alpine owns two things the server cannot do well: keeping a
            running total as the keys land, and moving the caret between cells.
            Everything else stays in PHP.
        --}}
        <div
            x-data="{
                debit: 0,
                credit: 0,

                get balanced() {
                    return this.debit > 0 && Math.abs(this.debit - this.credit) < 0.000001;
                },

                format(value) {
                    return (Number(value) || 0).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },

                cell(row, field) {
                    return this.$root.querySelector('[data-row=&quot;' + row + '&quot;][data-field=&quot;' + field + '&quot;]');
                },

                recalc() {
                    let debit = 0;
                    let credit = 0;

                    this.$root.querySelectorAll('[data-field=&quot;debit&quot;]').forEach((input) => {
                        const row = input.dataset.row;
                        const rate = Number(this.cell(row, 'rate')?.value || 1) || 1;

                        debit += (Number(input.value) || 0) * rate;
                        credit += (Number(this.cell(row, 'credit')?.value) || 0) * rate;
                    });

                    this.debit = debit;
                    this.credit = credit;
                },

                onInput(event) {
                    const side = event.target.dataset.side;

                    if (side && Number(event.target.value) > 0) {
                        const other = this.cell(event.target.dataset.row, side === 'debit' ? 'credit' : 'debit');

                        if (other && other.value !== '') {
                            other.value = '';
                            other.dispatchEvent(new Event('input', { bubbles: false }));
                        }
                    }

                    this.recalc();
                },

                focusCell(row, field) {
                    const target = this.cell(row, field);

                    if (target) {
                        target.focus();
                        target.select?.();
                    }
                },

                onKeydown(event) {
                    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                        event.preventDefault();
                        this.$wire.saveDraft().then(() => this.recalc());

                        return;
                    }

                    const row = event.target.dataset?.row;

                    if (row === undefined) {
                        return;
                    }

                    const rowIndex = Number(row);
                    const field = event.target.dataset.field;
                    const isSelect = event.target.tagName === 'SELECT';

                    if (event.key === 'ArrowDown' && ! isSelect) {
                        event.preventDefault();
                        this.focusCell(rowIndex + 1, field);
                    } else if (event.key === 'ArrowUp' && ! isSelect) {
                        event.preventDefault();
                        this.focusCell(rowIndex - 1, field);
                    } else if (event.key === 'Enter') {
                        event.preventDefault();

                        if (this.cell(rowIndex + 1, field)) {
                            this.focusCell(rowIndex + 1, field);
                        } else {
                            this.$wire.addRow().then(() => {
                                this.recalc();
                                this.$nextTick(() => this.focusCell(rowIndex + 1, 'account'));
                            });
                        }
                    }
                },
            }"
            x-init="recalc()"
            x-on:input="onInput($event)"
            x-on:keydown="onKeydown($event)"
        >
            <flux:card class="overflow-hidden p-0">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[72rem] text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                            <tr class="text-start text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                <th class="w-10 px-3 py-2 text-start">#</th>
                                <th class="min-w-56 px-3 py-2 text-start">{{ __('Account') }}</th>
                                <th class="min-w-44 px-3 py-2 text-start">{{ __('Cost Centre') }}</th>
                                <th class="min-w-52 px-3 py-2 text-start">{{ __('Description') }}</th>
                                <th class="min-w-40 px-3 py-2 text-start">{{ __('Analysis') }}</th>
                                <th class="w-28 px-3 py-2 text-start">{{ __('Currency') }}</th>
                                <th class="w-24 px-3 py-2 text-start">{{ __('Rate') }}</th>
                                <th class="w-32 px-3 py-2 text-end">{{ __('Debit') }}</th>
                                <th class="w-32 px-3 py-2 text-end">{{ __('Credit') }}</th>
                                <th class="w-10 px-3 py-2"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($rows as $index => $row)
                                <tr wire:key="journal-row-{{ $index }}-{{ $row['id'] ?? 'new' }}" class="align-top">
                                    <td class="px-3 py-2 text-zinc-400 tabular-nums">{{ $index + 1 }}</td>

                                    <td class="px-3 py-2">
                                        <flux:input
                                            size="sm"
                                            list="gl-accounts"
                                            wire:model="rows.{{ $index }}.account_id"
                                            :disabled="! $editable"
                                            data-row="{{ $index }}"
                                            data-field="account"
                                            placeholder="{{ __('Number or name') }}"
                                        />
                                        <flux:error name="rows.{{ $index }}.account_id" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <flux:input
                                            size="sm"
                                            list="gl-cost-centers"
                                            wire:model="rows.{{ $index }}.cost_center_id"
                                            :disabled="! $editable"
                                            data-row="{{ $index }}"
                                            data-field="cost_center"
                                        />
                                    </td>

                                    <td class="px-3 py-2">
                                        <flux:input
                                            size="sm"
                                            wire:model="rows.{{ $index }}.description"
                                            :disabled="! $editable"
                                            data-row="{{ $index }}"
                                            data-field="description"
                                        />
                                    </td>

                                    <td class="px-3 py-2">
                                        <div class="flex gap-1">
                                            <flux:input
                                                size="sm"
                                                class="w-24"
                                                wire:model="rows.{{ $index }}.analysis_code"
                                                :disabled="! $editable"
                                                data-row="{{ $index }}"
                                                data-field="analysis_code"
                                                placeholder="{{ __('Code') }}"
                                            />
                                            <flux:input
                                                size="sm"
                                                wire:model="rows.{{ $index }}.analysis_name"
                                                :disabled="! $editable"
                                                data-row="{{ $index }}"
                                                data-field="analysis_name"
                                                placeholder="{{ __('Name') }}"
                                            />
                                        </div>
                                    </td>

                                    <td class="px-3 py-2">
                                        <flux:select
                                            size="sm"
                                            wire:model="rows.{{ $index }}.currency_id"
                                            :disabled="! $editable"
                                            data-row="{{ $index }}"
                                            data-field="currency"
                                        >
                                            @foreach ($this->currencyOptions() as $currency)
                                                <flux:select.option wire:key="cur-{{ $index }}-{{ $currency->id }}" value="{{ $currency->id }}">
                                                    {{ $currency->code }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </td>

                                    <td class="px-3 py-2">
                                        {{-- Text, not number: a number input steals the arrow keys the
                                             grid uses to move between rows. --}}
                                        <flux:input
                                            size="sm"
                                            inputmode="decimal"
                                            class="text-end tabular-nums"
                                            wire:model="rows.{{ $index }}.exchange_rate"
                                            :disabled="! $editable"
                                            data-row="{{ $index }}"
                                            data-field="rate"
                                            data-rate
                                        />
                                    </td>

                                    <td class="px-3 py-2">
                                        <flux:input
                                            size="sm"
                                            inputmode="decimal"
                                            class="text-end tabular-nums"
                                            wire:model="rows.{{ $index }}.debit"
                                            :disabled="! $editable"
                                            data-row="{{ $index }}"
                                            data-field="debit"
                                            data-side="debit"
                                        />
                                        <flux:error name="rows.{{ $index }}.debit" />
                                    </td>

                                    <td class="px-3 py-2">
                                        <flux:input
                                            size="sm"
                                            inputmode="decimal"
                                            class="text-end tabular-nums"
                                            wire:model="rows.{{ $index }}.credit"
                                            :disabled="! $editable"
                                            data-row="{{ $index }}"
                                            data-field="credit"
                                            data-side="credit"
                                        />
                                        <flux:error name="rows.{{ $index }}.credit" />
                                    </td>

                                    <td class="px-3 py-2 text-center">
                                        @if ($editable)
                                            <flux:button
                                                size="xs"
                                                variant="subtle"
                                                icon="x-mark"
                                                x-on:click="$wire.removeRow({{ $index }}).then(() => recalc())"
                                                tabindex="-1"
                                                aria-label="{{ __('Remove line') }} {{ $index + 1 }}"
                                            />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        {{-- Totals stay visible while the grid scrolls under them. --}}
                        <tfoot class="border-t-2 border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">
                            <tr class="font-medium">
                                <td colspan="7" class="px-3 py-3 text-end text-zinc-500 dark:text-zinc-400">
                                    {{ __('Totals') }}
                                </td>
                                <td class="px-3 py-3 text-end tabular-nums" x-text="format(debit)">{{ number_format((float) $totals['debit'], 2) }}</td>
                                <td class="px-3 py-3 text-end tabular-nums" x-text="format(credit)">{{ number_format((float) $totals['credit'], 2) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="7" class="px-3 pb-3 text-end text-zinc-500 dark:text-zinc-400">
                                    {{ __('Difference') }}
                                </td>
                                <td
                                    colspan="2"
                                    class="px-3 pb-3 text-end tabular-nums"
                                    x-bind:class="balanced ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                >
                                    <span x-text="format(debit - credit)">{{ number_format((float) $totals['difference'], 2) }}</span>
                                    <span x-show="balanced" class="ms-1">&check;</span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </flux:card>

            {{-- Native filtering: the browser matches as you type, with no request. --}}
            <datalist id="gl-accounts">
                @foreach ($this->accountOptions() as $account)
                    <option wire:key="acc-opt-{{ $account->id }}" value="{{ $account->id }}">
                        {{ $account->number }} — {{ $account->name }}
                    </option>
                @endforeach
            </datalist>

            <datalist id="gl-cost-centers">
                @foreach ($this->costCenterOptions() as $costCenter)
                    <option wire:key="cc-opt-{{ $costCenter->id }}" value="{{ $costCenter->id }}">
                        {{ $costCenter->number }} — {{ $costCenter->name }}
                    </option>
                @endforeach
            </datalist>

            @if ($editable)
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Driven through Alpine so the running total is refreshed
                             the moment the new row lands, with no extra round trip. --}}
                        <flux:button
                            size="sm"
                            variant="subtle"
                            icon="plus"
                            x-on:click="$wire.addRow().then(() => recalc())"
                        >
                            {{ __('Add line') }}
                        </flux:button>

                        <flux:text size="sm" class="text-zinc-500">
                            {{ __('Arrows move between rows · Enter on the last row adds one · Ctrl+S saves') }}
                        </flux:text>
                    </div>

                    {{--
                        Saving the grid is the only thing this screen does.
                        Posting, reversing, editing the header and deleting are
                        the document's actions, and they live on its record
                        view where all of them are visible at once.
                    --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button
                            variant="primary"
                            x-on:click="$wire.saveDraft().then(() => recalc())"
                            wire:loading.attr="disabled"
                            wire:target="saveDraft"
                        >
                            {{ __('Save draft') }}
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </section>

</div>
