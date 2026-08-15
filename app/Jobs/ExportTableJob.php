<?php

namespace App\Jobs;

use App\Livewire\DynamicTable\Table;
use App\Models\User;
use App\Notifications\TableExportFailed;
use App\Notifications\TableExportReady;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Rebuilds a Dynamic Table's export off the request cycle. The table class is
 * re-instantiated and its definition/query rebuilt from scratch — only the
 * primitive state travels through the queue, never a builder or a model.
 */
class ExportTableJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    /**
     * @param  class-string<Table>  $tableClass
     * @param  array<string, int|string>  $context
     * @param  array<string, mixed>  $state
     * @param  array<int, string>  $selectedIds
     */
    public function __construct(
        public string $tableClass,
        public array $context,
        public array $state,
        public array $selectedIds,
        public int $userId,
    ) {}

    public function handle(): void
    {
        abort_unless(is_subclass_of($this->tableClass, Table::class), 400);

        $user = User::findOrFail($this->userId);

        // The table's query() gates on the acting user (permissions, tenancy),
        // and the queue has no session — so the export runs as its requester.
        Auth::setUser($user);

        $table = app($this->tableClass);

        foreach ($this->context as $property => $value) {
            $table->{$property} = $value;
        }

        $export = $table->writeExport($this->state, $this->selectedIds);

        $user->notify(new TableExportReady($export['path'], $export['filename'], $export['rows']));
    }

    /**
     * A queued export that dies must not die silently — the user was told to
     * wait for a notification, so the failure becomes one too.
     */
    public function failed(Throwable $exception): void
    {
        User::find($this->userId)?->notify(new TableExportFailed($exception->getMessage()));
    }
}
