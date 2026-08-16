<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\DateViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Livewire\GeneralLedger\FiscalYears\FiscalPeriodsTable;
use Modules\Finance\Models\GeneralLedger\FiscalYear;

/**
 * The authorized record show page for a single FiscalYear (fin-gl-fyr).
 */
class FiscalYearRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.general-ledger.fiscal-year';

    public function model(): string
    {
        return FiscalYear::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(FiscalYear::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return FiscalYear::query()->whereRaw('1 = 0');
        }

        return FiscalYear::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->start_date->toDateString().' → '.$record->end_date->toDateString();
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
                            DateViewField::make('start_date')->label('Start Date'),
                            DateViewField::make('end_date')->label('End Date'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('periods')
                ->applicationKey('finance.general-ledger.fiscal-year.periods')
                ->label('Periods')
                ->table(FiscalPeriodsTable::class)
                ->relation('periods')
                ->authorization(true),
        ];
    }
}
