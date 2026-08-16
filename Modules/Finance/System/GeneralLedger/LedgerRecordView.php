<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\Finance\Models\GeneralLedger\LedgerConversionType;
use Modules\Finance\Models\GeneralLedger\RateType;

/**
 * The authorized record show page for a single Ledger (fin-gl-led).
 */
class LedgerRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.general-ledger.ledger';

    public function model(): string
    {
        return Ledger::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Ledger::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Ledger::query()->whereRaw('1 = 0');
        }

        return Ledger::query()->where('is_active', true)->with('baseCurrency');
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->is_primary ? __('Primary ledger') : __('Secondary ledger');
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
                            RecordReferenceViewField::make('entity')
                                ->applicationCode('hr-org-ent')
                                ->relation('entity')
                                ->label('Entity'),
                            RecordReferenceViewField::make('chart')
                                ->applicationCode(Chart::APPLICATION_CODE)
                                ->relation('chart')
                                ->label('Chart of Accounts'),
                            RelationViewField::make('baseCurrency.name')->label('Base Currency'),
                        ]),
                    FieldsContent::make('conversion')
                        ->heading('Conversion')
                        ->fields([
                            BooleanViewField::make('is_primary')->label('Primary Ledger'),
                            RecordReferenceViewField::make('primaryLedger')
                                ->applicationCode(Ledger::APPLICATION_CODE)
                                ->relation('primaryLedger')
                                ->label('Fed From'),
                            EnumViewField::make('conversion_type')
                                ->label('Differs By')
                                ->labels(LedgerConversionType::options()),
                            EnumViewField::make('rate_type')
                                ->label('Rate Type')
                                ->labels(RateType::options()),
                            RecordReferenceViewField::make('roundingAccount')
                                ->applicationCode(Account::APPLICATION_CODE)
                                ->relation('roundingAccount')
                                ->label('Rounding Difference Account'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
