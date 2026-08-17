<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\SelectField;
use App\Support\DynamicForm\Core\Fields\TextareaField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\LedgerScope;

/**
 * Create-form definition for the "fin-gl-bok" Application.
 *
 * "Carried To" is the setting that decides whether documents of this kind reach
 * the tax books. Which specific ledgers a Selected book names is attached from
 * its record view afterward.
 */
class JournalBookForm extends DynamicForm
{
    public function model(): string
    {
        return JournalBook::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            TextField::make('sequence_prefix')
                ->label('Sequence Prefix')
                ->required()
                ->rules(['max:10', 'unique:journal_books,sequence_prefix']),
            SelectField::make('ledger_scope')
                ->label('Carried To')
                ->options(LedgerScope::options())
                ->required()
                ->rules(['in:'.implode(',', array_column(LedgerScope::cases(), 'value'))]),
            TextareaField::make('description')->label('Description'),
        ];
    }
}
