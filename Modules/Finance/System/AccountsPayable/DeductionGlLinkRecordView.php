<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\AccountsPayable\Deduction;
use Modules\Finance\Models\AccountsPayable\DeductionCategory;
use Modules\Finance\Models\AccountsPayable\DeductionGlLink;
use Modules\Finance\Models\GeneralLedger\Account;

/**
 * The authorized record show page for a single DeductionGlLink
 * (fin-ap-dgl).
 */
class DeductionGlLinkRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.accounts-payable.deduction-gl-link';

    public function model(): string
    {
        return DeductionGlLink::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(DeductionGlLink::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return DeductionGlLink::query()->whereRaw('1 = 0');
        }

        return DeductionGlLink::query()->with(['deductionCategory', 'deduction', 'account'])->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return $record->code;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->account?->name;
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
                            RecordReferenceViewField::make('deductionCategory')
                                ->applicationCode(DeductionCategory::APPLICATION_CODE)
                                ->relation('deductionCategory')
                                ->label('Deduction Category'),
                            RecordReferenceViewField::make('deduction')
                                ->applicationCode(Deduction::APPLICATION_CODE)
                                ->relation('deduction')
                                ->label('Deduction'),
                            RecordReferenceViewField::make('account')
                                ->applicationCode(Account::APPLICATION_CODE)
                                ->relation('account')
                                ->label('GL Account'),
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
