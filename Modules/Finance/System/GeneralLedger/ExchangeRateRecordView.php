<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\DateViewField;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;
use Modules\Finance\Models\GeneralLedger\RateType;

/**
 * The authorized record show page for a single ExchangeRate (fin-gl-rat).
 */
class ExchangeRateRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.general-ledger.exchange-rate';

    public function model(): string
    {
        return ExchangeRate::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(ExchangeRate::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return ExchangeRate::query()->whereRaw('1 = 0');
        }

        return ExchangeRate::query()->with(['fromCurrency', 'toCurrency']);
    }

    public function title(mixed $record): string
    {
        return (string) $record->rate;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->rate_date->toDateString();
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
                            RelationViewField::make('fromCurrency.name')->label('From Currency'),
                            RelationViewField::make('toCurrency.name')->label('To Currency'),
                            DateViewField::make('rate_date')->label('Rate Date'),
                            TextViewField::make('rate')->label('Rate'),
                            EnumViewField::make('rate_type')
                                ->label('Rate Type')
                                ->labels(RateType::options()),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
