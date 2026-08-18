<?php

namespace Modules\General\Livewire\System\Backups;

use App\Livewire\Concerns\ChecksApplicationAccess;
use App\Livewire\DynamicTable\Table;
use App\Models\BackupLog;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\DateFilter;
use App\Support\DynamicTable\Core\Filters\EnumFilter;
use App\Support\DynamicTable\Core\Sort;
use Illuminate\Database\Eloquent\Builder;

class BackupsTable extends Table
{
    use ChecksApplicationAccess;

    protected string $tableKey = 'general-system-backups';

    protected function applicationCode(): string
    {
        return BackupLog::APPLICATION_CODE;
    }

    protected function query(): Builder
    {
        return BackupLog::query()
            ->with('user')
            ->orderBy('created_at', 'desc');
    }

    protected function columns(): array
    {
        return [
            TextColumn::make('created_at')
                ->sortable()
                ->label('Date'),
            TextColumn::make('file_size')
                ->label('Size'),
            TextColumn::make('status')
                ->sortable()
                ->label('Status'),
            TextColumn::make('user.name')
                ->label('Created By'),
        ];
    }

    protected function filters(): array
    {
        return [
            EnumFilter::make('status')
                ->options(['success' => 'Success', 'failure' => 'Failure']),
            DateFilter::make('created_at'),
        ];
    }

    protected function defaultSort(): array
    {
        return [Sort::make('created_at')->descending()];
    }
}
