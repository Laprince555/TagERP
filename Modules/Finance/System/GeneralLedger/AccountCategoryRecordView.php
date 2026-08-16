<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\ComputedViewField;
use App\Support\DynamicRecordView\Core\Fields\NumberViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\AccountCategory;

/**
 * The authorized record show page for a single AccountCategory (fin-gl-cat).
 */
class AccountCategoryRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.general-ledger.account-category';

    public function model(): string
    {
        return AccountCategory::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(AccountCategory::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return AccountCategory::query()->whereRaw('1 = 0');
        }

        return AccountCategory::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->nature->label();
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
                            ComputedViewField::make('nature')
                                ->label('Nature')
                                ->using(fn (mixed $record): string => $record->nature->label()),
                            ComputedViewField::make('normal_balance')
                                ->label('Normal Balance')
                                ->using(fn (mixed $record): string => $record->nature->normalBalance()->label()),
                            ComputedViewField::make('statement')
                                ->label('Statement')
                                ->using(fn (mixed $record): string => $record->nature->statement()->label()),
                            NumberViewField::make('sort_order')->label('Sort Order'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
