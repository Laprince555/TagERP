<?php

namespace Modules\Finance\System\CashAndBanks;

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Field;
use App\Support\DynamicRecordView\Core\FieldsContent;
use App\Support\DynamicRecordView\Core\Tab;
use Modules\Finance\Models\CashAndBanks\Categories\BankCategory;

class BankCategoryRecordView extends DynamicRecordView
{
    public function model(): string
    {
        return BankCategory::class;
    }

    public function title(mixed $record): string
    {
        return $record->name ?? 'Bank Category';
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->description;
    }

    public function tabs(): array
    {
        return [
            Tab::make('Details')
                ->content(FieldsContent::make()
                    ->fields([
                        Field::make('name', 'Name'),
                        Field::make('code', 'Code'),
                        Field::make('slug', 'Slug'),
                        Field::make('parent_id', 'Parent Category')
                            ->relationDisplay('parent', 'name'),
                        Field::make('description', 'Description'),
                        Field::make('is_active', 'Active')->boolean(),
                    ])
                ),
        ];
    }
}
