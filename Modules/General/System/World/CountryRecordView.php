<?php

namespace Modules\General\System\World;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\RelationPicker;
use App\Support\DynamicRecordView\Core\RelationshipActions;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Livewire\World\Countries\CitiesTable;
use Nnjeim\World\Models\Country;

/**
 * The authorized record show page for a single Country (package model,
 * gen-wld-ctr Application). This is the route the Country
 * RecordReferenceProvider points to — the "record.world" package only ships
 * an index/API endpoint, so this show page is the missing piece.
 */
class CountryRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.world.country';

    public function model(): string
    {
        return Country::class;
    }

    public function query(): Builder
    {
        // Re-evaluated on every mount/action (the engine's existing 404
        // convention), so a disabled Application or a revoked permission
        // is enforced on the very next request, not just at initial mount.
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-wld-ctr');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Country::query()->whereRaw('1 = 0');
        }

        // Same record-level rule as CountryRecordReferenceProvider::authorize():
        // only active (status = 1) Countries are showable.
        return Country::query()->where('status', 1);
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->region ?: null;
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
                            TextViewField::make('iso2')->label('ISO2')->copyable(),
                            TextViewField::make('iso3')->label('ISO3')->copyable(),
                            TextViewField::make('phone_code')->label('Phone Code'),
                            TextViewField::make('region')->label('Region'),
                            TextViewField::make('subregion')->label('Subregion'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('cities')
                ->applicationKey('general.world.country.cities')
                ->label('Cities')
                ->table(CitiesTable::class)
                ->relation('cities')
                ->authorization(true)
                // Link-only, no Unlink: City.country_id is NOT NULL (see
                // vendor/nnjeim/world .../create_cities_table.php), so a City
                // can never be "unassigned" and the FK can never be set to
                // null — plain Unlink is impossible, same reasoning as
                // SubModuleRecordView::subApplications(). Unlike a genuinely
                // optional relation, that also means no City is ever a valid
                // Link candidate by default (none is ever unassigned), so
                // allowReassignment() is required to make Link do anything:
                // it lets an admin move a mis-seeded City from one Country to
                // this one (a real, useful correction against a NOT NULL FK),
                // not a fabricated "unassign" capability. See
                // docs/dynamic-record-view/embedded-tables.md for the worked
                // example.
                ->relationshipActions(
                    RelationshipActions::make()
                        ->linkExisting(
                            RelationPicker::make()
                                ->displayUsing('name')
                                ->searchable(['name', 'country_code'])
                                ->pageSize(5)
                                ->maximumLoadedResults(50),
                        )
                        ->linkAuthorization(fn ($user, $parent, $candidate) => $user !== null)
                        ->allowReassignment(),
                ),
        ];
    }
}
