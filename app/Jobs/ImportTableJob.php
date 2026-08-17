<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\ImportRow;
use App\Notifications\ImportFinished;
use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use App\Support\Import\FormRowImporter;
use App\Support\Import\ImportRowStatus;
use App\Support\Import\SpreadsheetReader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Mirror image of ExportTableJob: an upload can outlive any request timeout,
 * so the file is staged and imported off the request cycle and the user is
 * told through a notification.
 *
 * Two phases, deliberately separate. Staging writes every line of the file to
 * import_rows untouched; only then does the second phase try to create records
 * from them. That is what lets the progress page show "row 41 of 900 — failed,
 * here is why" while the job is still running, and it makes a retry resume
 * instead of re-importing what already succeeded.
 */
class ImportTableJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    protected const STAGE_BATCH = 500;

    protected const IMPORT_CHUNK = 200;

    public function __construct(public int $importId) {}

    public function handle(): void
    {
        $import = Import::with('user')->findOrFail($this->importId);

        // The form's create()/rules gate on the acting user, and the queue has
        // no session — so the import runs as the person who uploaded it.
        Auth::setUser($import->user);

        $import->update(['status' => Import::STATUS_RUNNING]);

        $importer = new FormRowImporter(
            app(FormDefinitionRegistry::class)->resolve($import->form_key),
        );

        $this->stage($import, $importer->headers());
        $this->importStagedRows($import, $importer);

        $import->refreshCounts();
        $import->update(['status' => Import::STATUS_COMPLETED]);

        $import->user->notify(new ImportFinished($import->fresh()));
    }

    /**
     * Writes the file's lines to import_rows verbatim. Skipped entirely when
     * rows already exist, so a retried job resumes rather than duplicating.
     *
     * @param  array<int, string>  $expectedHeaders
     */
    protected function stage(Import $import, array $expectedHeaders): void
    {
        if ($import->rows()->exists()) {
            return;
        }

        $rows = app(SpreadsheetReader::class)->rows(
            Storage::disk('local')->path($import->path),
        );

        $buffer = [];
        $total = 0;
        $now = now();

        foreach ($rows as $number => $payload) {
            if ($total === 0) {
                $this->assertHeadersUsable(array_keys($payload), $expectedHeaders);
            }

            $buffer[] = [
                'import_id' => $import->id,
                'row_number' => $number,
                'payload' => json_encode($payload),
                'status' => ImportRowStatus::Pending->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $total++;

            if (count($buffer) === self::STAGE_BATCH) {
                ImportRow::insert($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            ImportRow::insert($buffer);
        }

        $import->update(['total_rows' => $total]);
    }

    /**
     * A file whose header row matches nothing would fail every single line with
     * the same "field is required" — a whole-file failure is the honest answer.
     *
     * @param  array<int, string>  $actual
     * @param  array<int, string>  $expected
     */
    protected function assertHeadersUsable(array $actual, array $expected): void
    {
        if (array_intersect($actual, $expected) === []) {
            throw new \RuntimeException(__('The file\'s header row matches none of the template columns (:columns).', [
                'columns' => implode(', ', $expected),
            ]));
        }
    }

    /**
     * Each row is its own transaction: one bad line rolls back only itself and
     * is recorded with the reason, never taking the rest of the file with it.
     */
    protected function importStagedRows(Import $import, FormRowImporter $importer): void
    {
        $import->rows()
            ->where('status', ImportRowStatus::Pending)
            ->chunkById(self::IMPORT_CHUNK, function ($rows) use ($importer): void {
                foreach ($rows as $row) {
                    try {
                        DB::transaction(fn () => $importer->import($row->payload));

                        $row->update(['status' => ImportRowStatus::Imported, 'error' => null]);
                    } catch (Throwable $exception) {
                        $row->update([
                            'status' => ImportRowStatus::Failed,
                            'error' => $this->reason($exception),
                        ]);
                    }
                }
            });
    }

    /** Validation failures become the reader-facing message; anything else is the raw error. */
    protected function reason(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return implode(' ', collect($exception->errors())->flatten()->all());
        }

        return $exception->getMessage();
    }

    /**
     * A queued import that dies must not die silently — the user was told to
     * wait for a notification, so the failure becomes one too.
     */
    public function failed(Throwable $exception): void
    {
        $import = Import::with('user')->find($this->importId);

        if ($import === null) {
            return;
        }

        $import->refreshCounts();
        $import->update([
            'status' => Import::STATUS_FAILED,
            'error' => $exception->getMessage(),
        ]);

        $import->user?->notify(new ImportFinished($import->fresh()));
    }
}
