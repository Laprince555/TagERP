<?php

namespace App\Livewire\Imports;

use App\Livewire\DynamicTable\Table;
use App\Models\Import;
use App\Models\ImportRow;
use App\Support\DynamicTable\Core\Columns\ComputedColumn;
use App\Support\DynamicTable\Core\Columns\EnumColumn;
use App\Support\DynamicTable\Core\Columns\NumberColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\TextFilter;
use App\Support\DynamicTable\Core\Sort;
use App\Support\Import\ImportRowStatus;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

/**
 * Line-by-line result of one queued import: what was in the file, whether the
 * record was created, and — when it wasn't — why. Scoped to a single import
 * owned by the acting user; ownership is re-checked on every render, never
 * only at mount.
 */
class ImportRowsTable extends Table
{
    protected string $tableKey = 'import-rows';

    #[Locked]
    public int $importId = 0;

    /** $importId trails the inherited parameters so the signature stays compatible; Livewire passes it by name. */
    public function mount(
        string $embedRecordViewKey = '',
        int|string $embedRecordId = '',
        string $embedSection = '',
        string $embedTab = '',
        string $embedContent = '',
        int $importId = 0,
    ): void {
        $this->importId = $importId;

        parent::mount($embedRecordViewKey, $embedRecordId, $embedSection, $embedTab, $embedContent);
    }

    /** Cheap re-render while the job is still working through the file. */
    #[On('refresh-import-rows')]
    public function refreshRows(): void {}

    protected function query(): Builder
    {
        $owned = Import::query()
            ->whereKey($this->importId)
            ->where('user_id', auth()->id())
            ->exists();

        if (! $owned) {
            return ImportRow::query()->whereRaw('1 = 0');
        }

        return ImportRow::query()->where('import_id', $this->importId);
    }

    protected function columns(): array
    {
        return [
            NumberColumn::make('row_number')->sortable()->label('#')->width('4rem'),
            EnumColumn::make('status')
                ->enum(ImportRowStatus::class)
                ->sortable()
                ->label('Status')
                ->formatUsing(fn ($value): string => ($value instanceof ImportRowStatus ? $value : ImportRowStatus::from($value))->label()),
            ComputedColumn::make('data')
                ->field('payload')
                ->label('Row data')
                ->formatUsing(fn ($value): string => $this->summarize((array) $value)),
            TextColumn::make('error')->searchable()->label('Reason')->placeholder('—'),
        ];
    }

    protected function filters(): array
    {
        return [
            TextFilter::make('status'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('row_number')->ascending()];
    }

    /** Without this the queued export of this table would lose its scope. */
    protected function exportContext(): array
    {
        return [...parent::exportContext(), 'importId' => $this->importId];
    }

    /**
     * The file's own cells, rendered back as "column: value" so a failed line
     * is recognizable without opening the spreadsheet again.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function summarize(array $payload): string
    {
        return collect($payload)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value, $key): string => $key.': '.$value)
            ->implode(' · ');
    }
}
