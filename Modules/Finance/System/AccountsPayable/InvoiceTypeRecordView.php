<?php

namespace Modules\Finance\System\AccountsPayable;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\AccountsPayable\ApInvoiceType;

/**
 * The authorized record show page for a single ApInvoiceType
 * (fin-ap-ivt).
 */
class InvoiceTypeRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.accounts-payable.invoice-type';

    public function model(): string
    {
        return ApInvoiceType::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(ApInvoiceType::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return ApInvoiceType::query()->whereRaw('1 = 0');
        }

        return ApInvoiceType::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return $record->name;
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
                            TextViewField::make('name')->label('Name'),
                            TextViewField::make('description')->label('Description'),
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
