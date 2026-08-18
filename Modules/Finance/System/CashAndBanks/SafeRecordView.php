<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Field;
use App\Support\DynamicRecordView\Core\FieldsContent;
use App\Support\DynamicRecordView\Core\Tab;
use Modules\Finance\Models\CashAndBanks\Safes\Safe;

class SafeRecordView extends DynamicRecordView
{
    public function model(): string
    {
        return Safe::class;
    }

    public function title(mixed $record): string
    {
        return $record->name ?? 'Safe';
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->location;
    }

    public function tabs(): array
    {
        return [
            Tab::make('Details')
                ->content(FieldsContent::make()
                    ->fields([
                        Field::make('name', 'Safe Name'),
                        Field::make('code', 'Code'),
                        Field::make('entity_id', 'Entity')
                            ->relationDisplay('entity', 'name'),
                        Field::make('employee_id', 'Responsible Employee')
                            ->relationDisplay('employee', 'name'),
                        Field::make('location', 'Location'),
                        Field::make('description', 'Description'),
                        Field::make('gl_account_id', 'GL Account')
                            ->relationDisplay('glAccount', 'name'),
                        Field::make('is_active', 'Active')->boolean(),
                    ])
                ),
        ];
    }
}
