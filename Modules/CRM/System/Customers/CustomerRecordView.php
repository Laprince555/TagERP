<?php

namespace Modules\CRM\System\Customers;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\CRM\Models\Customers\Customer;
use Modules\CRM\Models\Customers\CustomerType;
use Modules\Finance\Models\AccountsReceivable\CustomerCategory;

/**
 * The authorized record show page for a single Customer (crm-cust-cus).
 */
class CustomerRecordView extends DynamicRecordView
{
    protected string $viewKey = 'crm.customer-management.customer';

    public function model(): string
    {
        return Customer::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Customer::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Customer::query()->whereRaw('1 = 0');
        }

        return Customer::query()->with(['company', 'category', 'defaultCurrency']);
    }

    public function title(mixed $record): string
    {
        return $record->company->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->code;
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
                            RecordReferenceViewField::make('company')
                                ->applicationCode('gen-com-com')
                                ->relation('company')
                                ->label('Company'),
                            RecordReferenceViewField::make('category')
                                ->applicationCode(CustomerCategory::APPLICATION_CODE)
                                ->relation('category')
                                ->label('Customer Category'),
                            EnumViewField::make('customer_type')
                                ->label('Customer Type')
                                ->labels(CustomerType::options()),
                            RelationViewField::make('defaultCurrency.name')->label('Default Currency'),
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
