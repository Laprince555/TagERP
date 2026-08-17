<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\EmptyStateContent;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\ComputedViewField;
use App\Support\DynamicRecordView\Core\Fields\DateTimeViewField;
use App\Support\DynamicRecordView\Core\Fields\DateViewField;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordAction;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalLinesTable;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalStatus;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\Finance\Services\GeneralLedger\AccountAccessResolver;
use Modules\Finance\Services\GeneralLedger\JournalPoster;

/**
 * The authorized record show page for a single Journal (fin-gl-jou).
 */
class JournalRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.general-ledger.journal';

    public function model(): string
    {
        return Journal::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Journal::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Journal::query()->whereRaw('1 = 0');
        }

        return Journal::query();
    }

    public function title(mixed $record): string
    {
        return (string) ($record->number ?? $record->code);
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->status->label().' · '.$record->journal_date->toDateString();
    }

    /**
     * The journal's whole action set, gated one permission each
     * (fin-gl-jou.update / .delete / .post / .reverse — all already generated
     * by permissions:sync from this Application's standard and custom
     * actions). Edit changes the document header; Edit lines opens the grid
     * that changes journal_lines; the two are deliberately separate screens
     * because the grid edits many rows at once and the header does not.
     */
    public function actions(): array
    {
        return [
            RecordAction::make('edit')
                ->label('Edit')
                ->icon('pencil-square')
                ->permission('update')
                ->variant('primary')
                ->form('finance.general-ledger.journal.create')
                ->visible(fn (mixed $record): bool => $this->isDraft($record)),

            // Same form as Edit, prefilled from this journal but saved as a new
            // draft — so the next month's recurring entry is one click, not a
            // re-typed header. Gated by create, not update: a posted journal
            // cannot be edited but is still a fine template.
            RecordAction::make('copy')
                ->label('Copy')
                ->icon('document-duplicate')
                ->permission('create')
                ->form('finance.general-ledger.journal.create', copy: true),

            RecordAction::make('edit-lines')
                ->label('Edit lines')
                ->icon('table-cells')
                ->permission('update')
                ->variant('filled')
                ->url(fn (mixed $record): string => route('finance.general-ledger.journals.edit', ['recordId' => $record->getKey()]))
                ->visible(fn (mixed $record): bool => $this->canSeeLines($record)),

            RecordAction::make('post')
                ->label('Post')
                ->icon('check-circle')
                ->variant('filled')
                ->confirm(__('Post this journal? Once posted it can only be corrected by a reversal.'))
                ->successMessage(__('Journal posted.'))
                ->visible(fn (mixed $record): bool => $this->isDraft($record)),

            RecordAction::make('reverse')
                ->label('Reverse')
                ->icon('arrow-uturn-left')
                ->variant('filled')
                ->confirm(__('Reverse this journal? A mirrored journal will be created and posted.'))
                // Never on a generated copy: reversing one creates a standalone
                // reversal in that secondary ledger alone, while the source
                // journal stays posted — the two sets of books then disagree,
                // which is exactly what replication exists to prevent. The
                // copy is cancelled by reversing its source (see
                // JournalPoster::reverse()).
                ->visible(fn (mixed $record): bool => $record->status === JournalStatus::Posted && ! $record->isGenerated()),

            RecordAction::make('delete')
                ->label('Delete')
                ->icon('trash')
                ->variant('danger')
                ->confirm(__('Delete this journal? This cannot be undone.'))
                ->visible(fn (mixed $record): bool => $this->isDraft($record)),
        ];
    }

    /**
     * Hands the document to the poster, which owns every accounting rule; a
     * refusal surfaces as the record view's error banner rather than a 500.
     */
    public function post(Journal $record): void
    {
        app(JournalPoster::class)->post($this->writable($record), auth()->id());
    }

    /** Lands on the reversal that was just created, not on the original. */
    public function reverse(Journal $record): string
    {
        $reversal = app(JournalPoster::class)->reverse($this->writable($record), auth()->id());

        return route('finance.general-ledger.journals.show', ['recordId' => $reversal->getKey()]);
    }

    /**
     * The instance a record view hands a handler is a *display* projection:
     * its relations are eager loaded with only the columns the fields on
     * screen need, so `ledger` arrives without `chart_id` and `ledger->chart`
     * reads as null. The poster walks the whole chart, so it gets a complete
     * model rather than the one that was rendered.
     */
    protected function writable(Journal $record): Journal
    {
        return Journal::findOrFail($record->getKey());
    }

    /** Back to the list, since the record this page showed is gone. */
    public function delete(Model $record): mixed
    {
        $record->delete();

        return route('finance.general-ledger.journals');
    }

    /** Editable in every sense: not posted, and not owned by another ledger. */
    protected function isDraft(mixed $record): bool
    {
        return $record instanceof Journal
            && $record->status->isEditable()
            && ! $record->isGenerated();
    }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')
                ->label('Overview')
                ->default()
                ->contents([
                    FieldsContent::make('document')
                        ->heading('Document')
                        ->fields([
                            TextViewField::make('number')->label('Number'),
                            DateViewField::make('journal_date')->label('Date'),
                            // Which company's books this lands in is the first
                            // thing to check when you keep several, so it sits
                            // beside the ledger rather than one click away
                            // inside it.
                            RelationViewField::make('entity.name')->label('Entity'),
                            RecordReferenceViewField::make('ledger')
                                ->applicationCode(Ledger::APPLICATION_CODE)
                                ->relation('ledger')
                                ->label('Ledger'),
                            RelationViewField::make('fiscalPeriod.name')->label('Fiscal Period'),
                            EnumViewField::make('status')
                                ->label('Status')
                                ->labels(JournalStatus::options()),
                            TextViewField::make('description')->label('Description'),
                        ]),
                    EmptyStateContent::make('lines-withheld')
                        ->message('The lines of this journal are hidden because you do not have permission to view all of its accounts.')
                        ->icon('lock-closed')
                        ->visible(fn (mixed $record): bool => ! $this->canSeeLines($record)),
                    FieldsContent::make('origin')
                        ->heading('Origin')
                        ->fields([
                            // The reference the originating module knows this
                            // document by — a purchase invoice number, say.
                            TextViewField::make('source_reference')->label('Source Reference'),
                            RecordReferenceViewField::make('reversesJournal')
                                ->applicationCode(Journal::APPLICATION_CODE)
                                ->relation('reversesJournal')
                                ->label('Reverses'),
                            // Totals are the sum of the lines, so they are
                            // withheld along with them — otherwise the size of
                            // what is hidden is still on display.
                            ComputedViewField::make('total_debit')
                                ->label('Total Debit')
                                ->using(fn (mixed $record): string => $this->canSeeLines($record) ? (string) $record->total_debit : '—'),
                            ComputedViewField::make('total_credit')
                                ->label('Total Credit')
                                ->using(fn (mixed $record): string => $this->canSeeLines($record) ? (string) $record->total_credit : '—'),
                            DateTimeViewField::make('posted_at')->label('Posted At'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('lines')
                ->applicationKey('finance.general-ledger.journal.lines')
                ->label('Lines')
                ->table(JournalLinesTable::class)
                ->relation('lines')
                ->authorization(fn (mixed $record): bool => $this->canSeeLines($record)),
        ];
    }

    /**
     * The journal itself stays listed and openable for everybody, so numbering
     * has no unexplained gaps; only its detail is withheld, and the reason is
     * stated rather than left looking like an empty document.
     */
    protected function canSeeLines(mixed $record): bool
    {
        return $record instanceof Journal
            && app(AccountAccessResolver::class)->canSeeAllAccountsOf($record);
    }
}
