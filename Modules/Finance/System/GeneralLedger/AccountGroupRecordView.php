<?php

namespace Modules\Finance\System\GeneralLedger;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Livewire\GeneralLedger\AccountGroups\AccountGroupAccountsTable;
use Modules\Finance\Models\GeneralLedger\AccountGroup;
use Modules\Finance\Models\GeneralLedger\AccountGroupPurpose;

/**
 * The authorized record show page for a single AccountGroup (fin-gl-agr).
 */
class AccountGroupRecordView extends DynamicRecordView
{
    protected string $viewKey = 'finance.general-ledger.account-group';

    public function model(): string
    {
        return AccountGroup::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(AccountGroup::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return AccountGroup::query()->whereRaw('1 = 0');
        }

        return AccountGroup::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->purpose->label();
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
                            EnumViewField::make('purpose')
                                ->label('Purpose')
                                ->labels(AccountGroupPurpose::options()),
                            TextViewField::make('description')->label('Description'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('accounts')
                ->applicationKey('finance.general-ledger.account-group.accounts')
                ->label('Accounts')
                ->table(AccountGroupAccountsTable::class)
                ->relation('accounts')
                ->authorization(true),
        ];
    }
}
