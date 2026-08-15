<?php

namespace Modules\General\System\World;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Livewire\World\People\PersonPositionsTable;
use Modules\General\Models\World\Companies\Company;

/**
 * The authorized record show page for a single Company (gen-wld-com
 * Application). Mirrors CountryRecordView's shape.
 */
class CompanyRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.world.company';

    public function model(): string
    {
        return Company::class;
    }

    public function query(): Builder
    {
        // Re-evaluated on every mount/action (the engine's existing 404
        // convention), so a disabled Application or a revoked permission
        // is enforced on the very next request, not just at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode(Company::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Company::query()->whereRaw('1 = 0');
        }

        // Same record-level rule as CompanyRecordReferenceProvider::authorize():
        // only active Companies are showable.
        return Company::query()->where('is_active', true);
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->tax_id ?: null;
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
                            TextViewField::make('tax_id')->label('Tax ID')->copyable(),
                            TextViewField::make('commercial_registration')->label('Commercial Registration')->copyable(),
                            RecordReferenceViewField::make('city')
                                ->applicationCode('gen-wld-cty')
                                ->relation('city')
                                ->label('City'),
                            TextViewField::make('address')->label('Address'),
                            TextViewField::make('phone')->label('Phone'),
                            TextViewField::make('email')->label('Email'),
                            TextViewField::make('website')->label('Website'),
                        ]),
                    FieldsContent::make('banking')
                        ->heading('Banking')
                        ->fields([
                            TextViewField::make('bank_account_1')->label('Bank Account 1'),
                            TextViewField::make('iban_1')->label('IBAN 1')->copyable(),
                            TextViewField::make('bank_account_2')->label('Bank Account 2'),
                            TextViewField::make('iban_2')->label('IBAN 2')->copyable(),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('positions')
                ->applicationKey('general.world.company.positions')
                ->label('People')
                ->table(PersonPositionsTable::class)
                ->relation('positions')
                ->authorization(true),
        ];
    }
}
