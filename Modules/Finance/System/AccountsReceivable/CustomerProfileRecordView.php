<?php

namespace Modules\Finance\System\AccountsReceivable;

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
use Modules\CRM\Models\Customers\Customer;
use Modules\Finance\Models\AccountsReceivable\CustomerFinancialRole;
use Modules\Finance\Models\AccountsReceivable\CustomerProfile;
use Modules\Finance\Models\GeneralLedger\Account;

/**
 * The authorized record show page for a single CustomerProfile (fin-ar-cpr).
 */
class CustomerProfileRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.accounts-receivable.customer-profile';

    public function model(): string
    {
        return CustomerProfile::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(CustomerProfile::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return CustomerProfile::query()->whereRaw('1 = 0');
        }

        return CustomerProfile::query()->with(['customer.company', 'currency', 'arAccount']);
    }

    public function title(mixed $record): string
    {
        return $record->customer->company->name.' — '.$record->financial_role->label();
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
                            RecordReferenceViewField::make('customer')
                                ->applicationCode(Customer::APPLICATION_CODE)
                                ->relation('customer')
                                ->label('Customer'),
                            EnumViewField::make('financial_role')
                                ->label('Financial Role')
                                ->labels(CustomerFinancialRole::options()),
                            RelationViewField::make('currency.name')->label('Currency'),
                            RecordReferenceViewField::make('arAccount')
                                ->applicationCode(Account::APPLICATION_CODE)
                                ->relation('arAccount')
                                ->label('AR Control Account'),
                            NumberViewField::make('credit_terms_days')->label('Credit Terms (Days)'),
                            NumberViewField::make('credit_limit')->label('Credit Limit'),
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
