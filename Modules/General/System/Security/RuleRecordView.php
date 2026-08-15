<?php

namespace Modules\General\System\Security;

use App\Models\Role;
use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Livewire\Security\Rules\RuleEmployeesTable;
use Modules\General\Livewire\Security\Rules\RuleJobTitlesTable;
use Modules\General\Livewire\Security\Rules\RulePermissionsTable;

/**
 * The authorized record show page for a single Rule (gen-sec-rul
 * Application) — a Spatie Role under a business-facing name. Read-only tabs
 * for now: attaching permissions/job-titles/employees is done via code/
 * tinker until a dedicated attach UI exists (same known gap already
 * documented for Department↔Entity and JobTitle↔JobGrade).
 */
class RuleRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.security.rule';

    public function model(): string
    {
        return Role::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-sec-rul');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Role::query()->whereRaw('1 = 0');
        }

        return Role::query();
    }

    public function title(mixed $record): string
    {
        return (string) $record->name;
    }

    public function subtitle(mixed $record): ?string
    {
        return null;
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
                            TextViewField::make('name')->label('Rule Name'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('permissions')
                ->applicationKey('general.security.rule.permissions')
                ->label('Permissions')
                ->table(RulePermissionsTable::class)
                ->relation('permissions')
                ->authorization(true),
            SubApplication::make('job-titles')
                ->applicationKey('general.security.rule.job-titles')
                ->label('Job Titles')
                ->table(RuleJobTitlesTable::class)
                ->relation('jobTitles')
                ->authorization(true),
            SubApplication::make('employees')
                ->applicationKey('general.security.rule.employees')
                ->label('Employees')
                ->table(RuleEmployeesTable::class)
                ->relation('employees')
                ->authorization(true),
        ];
    }
}
