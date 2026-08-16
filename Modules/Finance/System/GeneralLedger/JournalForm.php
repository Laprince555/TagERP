<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\DateField;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextareaField;
use App\Support\DynamicForm\Core\Fields\TextField;
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
