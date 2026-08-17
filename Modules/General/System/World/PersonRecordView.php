<?php

namespace Modules\General\System\World;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\RelationPicker;
use App\Support\DynamicRecordView\Core\RelationshipActions;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Livewire\World\People\PersonPositionsTable;
use Modules\General\Models\World\People\Person;

/**
 * The authorized record show page for a single Person (gen-wld-per
 * Application). Mirrors CountryRecordView's shape.
 */
class PersonRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.world.person';

    public function model(): string
    {
        return Person::class;
    }

    public function query(): Builder
    {
        // Re-evaluated on every mount/action (the engine's existing 404
        // convention), so a disabled Application or a revoked permission
        // is enforced on the very next request, not just at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode(Person::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Person::query()->whereRaw('1 = 0');
        }

        return Person::query();
    }

    public function title(mixed $record): string
    {
        return (string) $record->full_name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->nickname ?: null;
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
                            TextViewField::make('full_name')->label('Full Name'),
                            TextViewField::make('nickname')->label('Nickname'),
                            TextViewField::make('national_id')->label('National ID')->copyable(),
                            TextViewField::make('passport_number')->label('Passport Number')->copyable(),
                            TextViewField::make('gender')->label('Gender'),
                            TextViewField::make('date_of_birth')->label('Date of Birth'),
                            RecordReferenceViewField::make('city')
                                ->applicationCode('gen-wld-cty')
                                ->relation('city')
                                ->label('City'),
                            TextViewField::make('address')->label('Address'),
                            TextViewField::make('phone')->label('Phone'),
                            TextViewField::make('email')->label('Email'),
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
                ->applicationKey('general.world.person.positions')
                ->label('Positions')
                ->table(PersonPositionsTable::class)
                ->relation('positions')
                ->authorization(true)
                ->relationshipActions(
                    RelationshipActions::make()
                        ->linkExisting(
                            RelationPicker::make()
                                ->displayUsing('position')
                                ->searchable(['position', 'slug'])
                                ->pageSize(5)
                                ->maximumLoadedResults(50),
                        )
                        // Reassignment moves a position row from one person to
                        // another — employment history, not a cosmetic link —
                        // so it takes the same grant as editing a person.
                        ->linkAuthorization(fn ($user, $parent, $candidate) => (bool) $user?->can('gen-wld-per.update'))
                        ->allowReassignment()

                ),

        ];
    }
}
