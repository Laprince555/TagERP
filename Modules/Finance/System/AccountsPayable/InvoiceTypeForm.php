<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\TextField;
use Modules\Finance\Models\AccountsPayable\ApInvoiceType;

/**
 * Create-form definition for the "fin-ap-ivt" Application.
 */
class InvoiceTypeForm extends DynamicForm
{
    public function model(): string
    {
        return ApInvoiceType::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            TextField::make('description')->label('Description'),
        ];
    }
}
