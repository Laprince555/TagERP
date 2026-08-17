<?php

namespace Modules\General\System\System;

use App\Models\User;
use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\RelationPicker;
use App\Support\DynamicRecordView\Core\RelationshipActions;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Livewire\Security\Roles\RolesTable;

/**
 * The authorized record show page for a single User (gen-sys-usr
 * Application). Mirrors CompanyRecordView's shape.
 */
class UserRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.system.user';

    public function model(): string
    {
        return User::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-sys-usr');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return User::query()->whereRaw('1 = 0');
        }

        return User::query();
    }

    public function applicationCode(): ?string
    {
        return 'gen-sys-usr';
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->email;
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
                            TextViewField::make('email')->label('Email')->copyable(),
                            // Not a RecordReferenceViewField: that component requires a
                            // BelongsTo relation, and here the FK is owned by Employee
                            // (employees.user_id), so User::employee() is a HasOne.
                            RelationViewField::make('employee.employee_number')->label('Employee'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('roles')
                ->applicationKey('general.system.user.roles')
                ->label('Roles')
                ->table(RolesTable::class)
                ->relation('roles')
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
                                ->searchable(['name'])
                                ->pageSize(5)
                                ->maximumLoadedResults(50),
                        )
                        // Attaching a role hands the target user everything
                        // that role can do, super_admin included, and
                        // detaching one can lock an administrator out of their
                        // own system — so both sides need the same explicit
                        // grant, never mere access to this page.
                        ->linkAuthorization(fn ($user, $parent, $candidate) => (bool) $user?->can('gen-sys-usr.assign'))
                        ->unlink()
                        ->unlinkAuthorization(fn ($user, $parent, $related) => (bool) $user?->can('gen-sys-usr.assign'))
                        ->allowReassignment()

                ),
        ];
    }
}
