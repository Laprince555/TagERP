<?php

namespace Modules\Finance\System\Tax;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\Tax\Tax;
use Modules\Finance\Models\Tax\TaxCategory;
use Modules\Finance\Models\Tax\TaxGlLink;

/**
 * The authorized record show page for a single TaxGlLink (fin-tax-gll).
 */
class TaxGlLinkRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.tax.tax-gl-link';

    public function model(): string
    {
        return TaxGlLink::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(TaxGlLink::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return TaxGlLink::query()->whereRaw('1 = 0');
        }

        return TaxGlLink::query()->with(['taxCategory', 'tax', 'account'])->where('is_active', true);
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
                            RecordReferenceViewField::make('taxCategory')
                                ->applicationCode(TaxCategory::APPLICATION_CODE)
                                ->relation('taxCategory')
                                ->label('Tax Category'),
                            RecordReferenceViewField::make('tax')
                                ->applicationCode(Tax::APPLICATION_CODE)
                                ->relation('tax')
                                ->label('Tax'),
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
