<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\NumberViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\AccountsPayable\VendorFinancialRole;
use Modules\Finance\Models\AccountsPayable\VendorProfile;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Procurement\Models\Vendors\Vendor;

/**
 * The authorized record show page for a single VendorProfile (fin-ap-vpr).
 */
class VendorProfileRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.accounts-payable.vendor-profile';

    public function model(): string
    {
        return VendorProfile::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(VendorProfile::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return VendorProfile::query()->whereRaw('1 = 0');
        }

        return VendorProfile::query()->with(['vendor.company', 'currency', 'apAccount']);
    }

    public function title(mixed $record): string
    {
        return $record->vendor->company->name.' — '.$record->financial_role->label();
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->currency->name;
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
                            RecordReferenceViewField::make('vendor')
                                ->applicationCode(Vendor::APPLICATION_CODE)
                                ->relation('vendor')
                                ->label('Vendor'),
                            EnumViewField::make('financial_role')
                                ->label('Financial Role')
                                ->labels(VendorFinancialRole::options()),
                            RelationViewField::make('currency.name')->label('Currency'),
                            RecordReferenceViewField::make('apAccount')
                                ->applicationCode(Account::APPLICATION_CODE)
                                ->relation('apAccount')
                                ->label('AP Control Account'),
                            NumberViewField::make('payment_terms_days')->label('Payment Terms (Days)'),
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
