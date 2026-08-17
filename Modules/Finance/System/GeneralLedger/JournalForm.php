<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextareaField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\GeneralLedger\FiscalPeriod;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\Ledger;

/**
 * Create-form definition for the "fin-gl-jou" Application — the journal header
 * only. Lines are added afterward, and nothing here posts anything: the
 * balance, period and account rules all live in JournalPoster so that every
 * writer is held to them, not just this screen.
 */
class JournalForm extends DynamicForm
{
    public function model(): string
    {
        return Journal::class;
    }

    /**
     * The same field set edits a draft: nothing here is consumed until the
     * journal posts — the document number is assigned by JournalPoster, and
     * the record's own `code` is built from a slug rather than from the book
     * — so moving a draft to another ledger or book costs nothing. A posted
     * or generated journal is corrected by reversing it, never by editing.
     */
    public function authorizeUpdate(Model $record): bool
    {
        /** @var Journal $record */
        return $record->status->isEditable() && ! $record->isGenerated();
    }

    /** Any journal the actor can open is a valid template for the next one. */
    public function authorizeCopy(Model $record): bool
    {
        return true;
    }

    /**
     * A copy is the whole document, not just its header: the new draft carries
     * the source's lines so the recurring entry is ready to post rather than
     * ready to re-type. Nothing about posting is carried over — no number, no
     * status, no posted_at — the copy starts as a plain draft, and its
     * balances are recomputed when it is posted like any other.
     */
    public function createCopy(array $data, Model $source): Model
    {
        /** @var Journal $source */
        return DB::transaction(function () use ($data, $source): Journal {
            /** @var Journal $journal */
            $journal = $this->create($data);

            foreach ($source->lines()->orderBy('line_number')->get() as $line) {
                $journal->lines()->create($line->only([
                    'line_number',
                    'account_id',
                    'cost_center_id',
                    'description',
                    'currency_id',
                    'exchange_rate',
                    'debit',
                    'credit',
                    'base_debit',
                    'base_credit',
                    'analysis_type',
                    'analysis_id',
                    'analysis_code',
                    'analysis_name',
                ]));
            }

            // The header totals are a stored aggregate of the lines (see
            // JournalEditor::saveDraft) — copied lines have to move them too,
            // or the new draft reads as an empty document.
            $journal->forceFill([
                'total_debit' => $journal->lines()->sum('base_debit'),
                'total_credit' => $journal->lines()->sum('base_credit'),
            ])->save();

            return $journal;
        });
    }

    public function fields(): array
    {
        return [
            RelationListField::make('ledger')
                ->model(Ledger::class)
                ->createForm('finance.general-ledger.ledger.create')
                ->field('name')
                ->column('ledger_id')
                ->label('Ledger')
                ->required(),
            RelationListField::make('journalBook')
                ->model(JournalBook::class)
                ->createForm('finance.general-ledger.journal-book.create')
                ->field('name')
                ->column('journal_book_id')
                ->label('Journal Book')
                ->required(),
            RelationListField::make('fiscalPeriod')
                ->model(FiscalPeriod::class)
                ->field('name')
                ->column('fiscal_period_id')
                ->label('Period')
                ->required(),
            DateField::make('journal_date')->label('Date')->required(),
            TextField::make('source_reference')->label('Source Reference'),
            TextareaField::make('description')->label('Description'),
        ];
    }
}
