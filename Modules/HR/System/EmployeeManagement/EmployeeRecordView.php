<?php

namespace Modules\HR\System\EmployeeManagement;

use App\Services\NavigationTreeService;
use App\Support\DynamicRecordView\Core\Content\FieldsContent;
use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Core\Fields\DateViewField;
use App\Support\DynamicRecordView\Core\Fields\EnumViewField;
use App\Support\DynamicRecordView\Core\Fields\MoneyViewField;
use App\Support\DynamicRecordView\Core\Fields\RecordReferenceViewField;
use App\Support\DynamicRecordView\Core\Fields\TextViewField;
use App\Support\DynamicRecordView\Core\RecordTab;
use App\Support\RecordReference\RecordReferenceAccess;
use Illuminate\Database\Eloquent\Builder;
use Modules\HR\Models\EmployeeManagement\Employee;

/**
 * The authorized record show page for a single Employee (hr-emp-emp
 * Application). Mirrors EntityRecordView's shape.
 */
class EmployeeRecordView extends DynamicRecordView
{
    protected string $viewKey = 'hr.employee-management.employee';

    public function model(): string
    {
        return Employee::class;
    }

    public function query(): Builder
    {
        $application = app(NavigationTreeService::class)->getApplicationByCode(Employee::APPLICATION_CODE);

        if (! app(RecordReferenceAccess::class)->applicationAccessible($application)) {
            return Employee::query()->whereRaw('1 = 0');
        }

        return Employee::query();
    }

    public function title(mixed $record): string
    {
        return $record->person?->full_name ?: $record->employee_number;
    }

    public function subtitle(mixed $record): ?string
    {
        return $record->employee_number;
    }

    public function tabs(): array
    {
        return [
            RecordTab::make('overview')
                ->label('Overview')
                ->default()
                ->contents([
                    FieldsContent::make('assignment')
                        ->heading('Assignment')
                        ->fields([
                            RecordReferenceViewField::make('person')
                                ->applicationCode('gen-wld-per')
                                ->relation('person')
                                ->label('Person'),
                            RecordReferenceViewField::make('entity')
                                ->applicationCode('hr-org-ent')
                                ->relation('entity')
                                ->label('Entity'),
                            RecordReferenceViewField::make('branch')
                                ->applicationCode('hr-org-brn')
                                ->relation('branch')
                                ->label('Branch'),
                            RecordReferenceViewField::make('department')
                                ->applicationCode('hr-org-dep')
                                ->relation('department')
                                ->label('Department'),
                            RecordReferenceViewField::make('jobTitle')
                                ->applicationCode('hr-org-jbt')
                                ->relation('jobTitle')
                                ->label('Job Title'),
                            RecordReferenceViewField::make('jobGrade')
                                ->applicationCode('hr-org-jbg')
                                ->relation('jobGrade')
                                ->label('Job Grade'),
                        ]),
                    FieldsContent::make('employment')
                        ->heading('Employment')
                        ->fields([
                            EnumViewField::make('status')
                                ->label('Status')
                                ->labels(['active' => 'Active', 'suspended' => 'Suspended', 'terminated' => 'Terminated']),
                            TextViewField::make('entity_scope')->label('Entity Scope'),
                            TextViewField::make('department_scope')->label('Department Scope'),
                            MoneyViewField::make('gross_salary')->label('Gross Salary'),
                            DateViewField::make('hire_date')->label('Hire Date'),
                            DateViewField::make('termination_date')->label('Termination Date'),
                        ]),
                ]),
        ];
    }

    public function subApplications(): array
    {
        return [];
    }
}
