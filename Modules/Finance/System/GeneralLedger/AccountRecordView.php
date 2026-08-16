<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\ComputedViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Livewire\GeneralLedger\Accounts\AccountChildrenTable;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\AccountCategory;
use Modules\Finance\Services\GeneralLedger\AccountAccessResolver;

/**
 * The authorized record show page for a single Account (fin-gl-acc).
 */
class AccountRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.general-ledger.account';

    public function model(): string
    {
        return Account::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Account::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Account::query()->whereRaw('1 = 0');
        }

        return app(AccountAccessResolver::class)->restrict(
            Account::query()->where('is_active', true)->withCount('children')
        );
    }

    public function title(mixed $record): string
    {
        return $record->number.' — '.$record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->is_postable ? __('Postable account') : __('Grouping account');
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
                            TextViewField::make('number')->label('Account Number'),
                            TextViewField::make('name')->label('Name'),
                            RecordReferenceViewField::make('parent')
                                ->applicationCode(Account::APPLICATION_CODE)
                                ->relation('parent')
                                ->label('Parent Account'),
                            RecordReferenceViewField::make('category')
                                ->applicationCode(AccountCategory::APPLICATION_CODE)
                                ->relation('category')
                                ->label('Category'),
                        ]),
                    FieldsContent::make('posting')
                        ->heading('Posting')
                        ->fields([
                            ComputedViewField::make('is_postable')
                                ->label('Accepts Postings')
                                ->using(fn (mixed $record): string => $record->is_postable ? __('Yes') : __('No')),
                            // Normal balance is not repeated here: the view engine
                            // narrows the related model's select, and the category
                            // reference above already leads to it.
                            TextViewField::make('required_analysis_type')->label('Required Analysis'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('children')
                ->applicationKey('finance.general-ledger.account.children')
                ->label('Child Accounts')
                ->table(AccountChildrenTable::class)
                ->relation('children')
                ->authorization(true),
        ];
    }
}
