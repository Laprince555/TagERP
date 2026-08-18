<?php

namespace Modules\Finance\System\Tax;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\DateViewField;
use App\Support\DynamicRecordView\Core\Fields\MoneyViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxAdjustment;

/**
 * The authorized record show page for a single TaxAdjustment (fin-tax-adj).
 */
class TaxAdjustmentRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.tax.tax-adjustment';

    public function model(): string
    {
        return TaxAdjustment::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(TaxAdjustment::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return TaxAdjustment::query()->whereRaw('1 = 0');
        }

        return TaxAdjustment::query()->with(['tax', 'createdBy']);
    }

    public function title(mixed $record): string
    {
        return $record->code;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->tax?->name;
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
                            RecordReferenceViewField::make('tax')
                                ->applicationCode(Tax::APPLICATION_CODE)
                                ->relation('tax')
                                ->label('Tax'),
                            DateViewField::make('adjustment_date')->label('Adjustment Date'),
                            TextViewField::make('reason')->label('Reason'),
                            MoneyViewField::make('amount')->label('Amount'),
                            TextViewField::make('description')->label('Description'),
                            TextViewField::make('status')->label('Status'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
