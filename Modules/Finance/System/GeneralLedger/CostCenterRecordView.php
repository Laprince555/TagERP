<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\ComputedViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\CostCenter;

/**
 * The authorized record show page for a single CostCenter (fin-gl-cct).
 */
class CostCenterRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.general-ledger.cost-center';

    public function model(): string
    {
        return CostCenter::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CostCenter::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return CostCenter::query()->whereRaw('1 = 0');
        }

        return CostCenter::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return $record->number.' — '.$record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->is_postable ? __('Accepts transactions') : __('Grouping cost centre');
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
                            TextViewField::make('number')->label('Number'),
                            TextViewField::make('name')->label('Name'),
                            RecordReferenceViewField::make('parent')
                                ->applicationCode(CostCenter::APPLICATION_CODE)
                                ->relation('parent')
                                ->label('Parent Cost Centre'),
                            BooleanViewField::make('accepts_transactions')->label('Accepts Transactions'),
                            ComputedViewField::make('is_postable')
                                ->label('Postable')
                                ->using(fn (mixed $record): string => $record->is_postable ? __('Yes') : __('No')),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
