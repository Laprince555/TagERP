<?php

namespace Modules\HR\System\Cycles;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\RelationListField;
use App\Support\DynamicForm\Core\Fields\TextareaField;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\HR\Models\Cycles\Cycle;
use Modules\HR\Models\Cycles\CycleType;

/**
 * Create-form definition for the "hr-cyc-cyc" Application. Header only —
 * cycle_lines are added afterward on the record view via the lines editor,
 * the same "create first, attach after" shape JournalEditor uses for lines.
 */
class CycleForm extends DynamicForm
{
    public function model(): string
    {
        return Cycle::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            RelationListField::make('cycleType')
                ->model(CycleType::class)
                ->field('name')
                ->createForm('hr.cycles.cycle-type.create')
                ->column('cycle_type_id')
                ->label('Cycle Type')
                ->required(),
            TextField::make('subject_model')
                ->label('Subject Model')
                ->helpText('Fully-qualified Eloquent model class this cycle governs, e.g. Modules\\Finance\\Models\\AccountsPayable\\ApInvoice.')
                ->required(),
            TextField::make('document_type_value')
                ->label('Document Type Value')
                ->helpText('Matches the subject\'s own classification value. Leave blank to apply to any document of that model.'),
            TextareaField::make('description')->label('Description'),
        ];
    }
}
