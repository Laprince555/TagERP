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
use Modules\General\Livewire\Security\Roles\RoleEmployeesTable;
use Modules\General\Livewire\Security\Roles\RoleJobTitlesTable;
use Modules\General\Livewire\Security\Roles\RolePermissionsTable;

/**
 * The authorized record show page for a single Role (gen-sec-rol
 * Application) — a Spatie Role under a business-facing name. Read-only tabs
 * for now: attaching permissions/job-titles/employees is done via code/
 * tinker until a dedicated attach UI exists (same known gap already
 * documented for Department↔Entity and JobTitle↔JobGrade).
 */
class RoleRecordView extends DynamicRecordView
{
    protected string $viewKey = 'general.security.role';

    public function model(): string
    {
        return Role::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode('gen-sec-rol');

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Role::query()->whereRaw('1 = 0');
        }

        return Role::query();
    }

    public function applicationCode(): ?string
    {
        return 'gen-sec-rol';
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
                            TextViewField::make('name')->label('Role Name'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('permissions')
                ->applicationKey('general.security.role.permissions')
                ->label('Permissions')
                ->table(RolePermissionsTable::class)
                ->relation('permissions')
                ->authorization(true),
            SubApplication::make('job-titles')
                ->applicationKey('general.security.role.job-titles')
                ->label('Job Titles')
                ->table(RoleJobTitlesTable::class)
                ->relation('jobTitles')
                ->authorization(true),
            SubApplication::make('employees')
                ->applicationKey('general.security.role.employees')
                ->label('Employees')
                ->table(RoleEmployeesTable::class)
                ->relation('employees')
                ->authorization(true),
        ];
    }
}
