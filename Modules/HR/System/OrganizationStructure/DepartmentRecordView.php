<?php

namespace Modules\HR\System\OrganizationStructure;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\NumberViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\DynamicRecordView\Core\SubApplication;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Livewire\OrganizationStructure\Departments\DepartmentEntitiesTable;
use Modules\HR\Models\OrganizationStructure\Department;

/**
 * The authorized record show page for a single Department (hr-org-dep
 * Application). Mirrors EntityRecordView's shape.
 */
class DepartmentRecordView extends DynamicRecordView
{
    protected string $viewKey = 'hr.organization-structure.department';

    public function model(): string
    {
        return Department::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Department::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Department::query()->whereRaw('1 = 0');
        }

        return Department::query()->where('is_active', true);
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
                            TextViewField::make('name')->label('Name'),
                            RecordReferenceViewField::make('parent')
                                ->applicationCode('hr-org-dep')
                                ->relation('parent')
                                ->label('Parent Department'),
                            NumberViewField::make('depth')->label('Depth in Tree'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [
            SubApplication::make('entities')
                ->applicationKey('hr.organization-structure.department.entities')
                ->label('Companies')
                ->table(DepartmentEntitiesTable::class)
                ->relation('entities')
                ->authorization(true),
        ];
    }
}
