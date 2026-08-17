<?php

namespace Modules\General\System;

use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\BooleanViewField;
use App\Support\DynamicRecordView\Core\Fields\RelationViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\RelationPicker;
use App\Support\DynamicRecordView\Core\RelationshipActions;
use App\Support\DynamicRecordView\Core\SubApplication;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Livewire\ApplicationsTable;

/**
 * Canonical Milestone 5 example: a SubModule record view whose Other Data
 * section embeds its real hasMany Applications relation (see
 * SubModule::applications()) through a real, reachable route.
 */
class SubModuleRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.sub-module';

    public function model(): string
    {
        return SubModule::class;
    }

    public function query(): Builder
    {
        return SubModule::query();
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->description ?: null;
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
                            TextViewField::make('code')->label('Code')->copyable(),
                            RelationViewField::make('module.name')->label('Module'),
                            BooleanViewField::make('is_active')->label('Active')
                                ->badge()->color(fn ($value) => $value ? 'lime' : 'zinc'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('applications')
                ->applicationKey('general.sub-module.applications')
                ->label('Applications')
                ->table(ApplicationsTable::class)
                ->relation('applications')
                ->authorization(true)
                // Link-only, no Unlink: Application.submodule_id is NOT NULL
                // (see 2026_07_30_160000_create_applications_table.php), so an
                // Application can never be "unassigned" and the FK can never
                // be set to null. allowReassignment() is required here — with
                // it off, no Application would ever be a valid Link candidate,
                // since none is ever unassigned; see
                // docs/dynamic-record-view/relationship-actions.md for the
                // full rationale. This is the canonical Milestone 5/6 mutation
                // example until a genuinely nullable-FK relation is available.
                ->relationshipActions(
                    RelationshipActions::make()
                        ->linkExisting(
                            RelationPicker::make()
                                ->displayUsing('name')
                                ->searchable(['name', 'code'])
                                ->pageSize(5)
                                ->maximumLoadedResults(50),
                        )
                        // Moving an Application re-parents a navigation node
                        // and, with it, a whole permission namespace — gated
                        // on the receiving SubModule's own update permission
                        // (permissions:sync generates `{code}.update` for
                        // every navigation node).
                        ->linkAuthorization(fn ($user, $parent, $candidate) => (bool) $user?->can($parent->code.'.update'))
                        ->allowReassignment(),
                ),
        ];
    }
}
