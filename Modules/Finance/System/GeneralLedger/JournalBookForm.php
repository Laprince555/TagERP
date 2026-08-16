<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\TextareaField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\GeneralLedger\JournalBook;

/**
 * Create-form definition for the "fin-gl-bok" Application.
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
            TextareaField::make('description')->label('Description'),
        ];
    }
}
