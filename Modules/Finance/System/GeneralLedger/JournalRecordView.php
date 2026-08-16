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
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalLinesTable;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalStatus;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\Finance\Services\GeneralLedger\AccountAccessResolver;

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
                            RecordReferenceViewField::make('ledger')
                                ->applicationCode(Ledger::APPLICATION_CODE)
                                ->relation('ledger')
                                ->label('Ledger'),
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
