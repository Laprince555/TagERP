<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;

/**
 * The authorized record show page for a single Deduction (fin-ap-ddc).
 */
class DeductionRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.accounts-payable.deduction';

    public function model(): string
    {
        return Deduction::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Deduction::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Deduction::query()->whereRaw('1 = 0');
        }

        return Deduction::query()->with('category')->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->code;
    }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')
                ->label('Overview')
                ->default()
                ->contents([
                    FieldsContent::make('basic-information')
                        ->heading('Basic Information')
                        ->fields([
                            TextViewField::make('name')->label('Name'),
                            RecordReferenceViewField::make('category')
                                ->applicationCode(DeductionCategory::APPLICATION_CODE)
                                ->relation('category')
                                ->label('Deduction Category'),
                            TextViewField::make('calculation_type')->label('Calculation Type'),
                            TextViewField::make('value')->label('Value'),
                            BooleanViewField::make('is_active')->label('Active'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
