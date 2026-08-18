<?php

namespace Modules\Finance\System\Tax;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxCategory;

/**
 * The authorized record show page for a single Tax (fin-tax-tax).
 */
class TaxRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.tax.tax';

    public function model(): string
    {
        return Tax::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Tax::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Tax::query()->whereRaw('1 = 0');
        }

        return Tax::query()->with('category')->where('is_active', true);
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
                                ->applicationCode(TaxCategory::APPLICATION_CODE)
                                ->relation('category')
                                ->label('Tax Category'),
                            TextViewField::make('direction')->label('Direction'),
                            TextViewField::make('rate')->label('Rate (%)'),
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
