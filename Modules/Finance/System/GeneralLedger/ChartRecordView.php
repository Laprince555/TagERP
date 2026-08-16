<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\NumberViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Livewire\GeneralLedger\Charts\ChartAccountsTable;
use Modules\Finance\Models\GeneralLedger\Chart;

/**
 * The authorized record show page for a single Chart (fin-gl-coa).
 */
class ChartRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.general-ledger.chart';

    public function model(): string
    {
        return Chart::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Chart::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Chart::query()->whereRaw('1 = 0');
        }

        return Chart::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return null;
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
                            TextViewField::make('description')->label('Description'),
                            NumberViewField::make('levels_count')->label('Levels'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('accounts')
                ->applicationKey('finance.general-ledger.chart.accounts')
                ->label('Accounts')
                ->table(ChartAccountsTable::class)
                ->relation('accounts')
                ->authorization(true),
        ];
    }
}
