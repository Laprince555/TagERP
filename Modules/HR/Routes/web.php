<?php

use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;
use Modules\HR\Livewire\Cycles\Cycles\CycleLinesEditor;
use Modules\HR\Livewire\Cycles\Cycles\CycleRecordView;
use Modules\HR\Livewire\Cycles\Cycles\CyclesIndex;
use Modules\HR\Livewire\Cycles\CycleTypes\CycleTypeRecordView;
use Modules\HR\Livewire\Cycles\CycleTypes\CycleTypesIndex;
use Modules\HR\Livewire\Cycles\Transactions\CycleTransactionRecordView;
use Modules\HR\Livewire\Cycles\Transactions\CycleTransactionReview;
use Modules\HR\Livewire\Cycles\Transactions\CycleTransactionsIndex;
use Modules\HR\Livewire\EmployeeManagement\Employees\EmployeeRecordView;
use Modules\HR\Livewire\EmployeeManagement\Employees\EmployeesIndex;
use Modules\HR\Livewire\OrganizationStructure\Branches\BranchesIndex;
use Modules\HR\Livewire\OrganizationStructure\Branches\BranchRecordView;
use Modules\HR\Livewire\OrganizationStructure\Departments\DepartmentRecordView;
use Modules\HR\Livewire\OrganizationStructure\Departments\DepartmentsIndex;
use Modules\HR\Livewire\OrganizationStructure\Entities\EntitiesIndex;
use Modules\HR\Livewire\OrganizationStructure\Entities\EntityRecordView;
use Modules\HR\Livewire\OrganizationStructure\JobGrades\JobGradeRecordView;
use Modules\HR\Livewire\OrganizationStructure\JobGrades\JobGradesIndex;
use Modules\HR\Livewire\OrganizationStructure\JobTitles\JobTitleRecordView;
use Modules\HR\Livewire\OrganizationStructure\JobTitles\JobTitlesIndex;

ModuleRoute::registerIndex('hr', '/hr', ModuleWorkspace::class);
ModuleRoute::registerSubModules('hr', '/hr', SubModuleWorkspace::class);

Route::middleware(['auth'])
    ->get('/hr/organization-structure/entities', EntitiesIndex::class)
    ->name('hr.organization-structure.entities');

Route::middleware(['auth'])
    ->get('/hr/organization-structure/entities/{recordId}/view', EntityRecordView::class)
    ->name('hr.organization-structure.entities.show');

Route::middleware(['auth'])
    ->get('/hr/organization-structure/branches', BranchesIndex::class)
    ->name('hr.organization-structure.branches');

Route::middleware(['auth'])
    ->get('/hr/organization-structure/branches/{recordId}/view', BranchRecordView::class)
    ->name('hr.organization-structure.branches.show');

Route::middleware(['auth'])
    ->get('/hr/organization-structure/departments', DepartmentsIndex::class)
    ->name('hr.organization-structure.departments');

Route::middleware(['auth'])
    ->get('/hr/organization-structure/departments/{recordId}/view', DepartmentRecordView::class)
    ->name('hr.organization-structure.departments.show');

Route::middleware(['auth'])
    ->get('/hr/organization-structure/job-grades', JobGradesIndex::class)
    ->name('hr.organization-structure.job-grades');

Route::middleware(['auth'])
    ->get('/hr/organization-structure/job-grades/{recordId}/view', JobGradeRecordView::class)
    ->name('hr.organization-structure.job-grades.show');

Route::middleware(['auth'])
    ->get('/hr/organization-structure/job-titles', JobTitlesIndex::class)
    ->name('hr.organization-structure.job-titles');

Route::middleware(['auth'])
    ->get('/hr/organization-structure/job-titles/{recordId}/view', JobTitleRecordView::class)
    ->name('hr.organization-structure.job-titles.show');

Route::middleware(['auth'])
    ->get('/hr/employee-management/employees', EmployeesIndex::class)
    ->name('hr.employee-management.employees');

Route::middleware(['auth'])
    ->get('/hr/employee-management/employees/{recordId}/view', EmployeeRecordView::class)
    ->name('hr.employee-management.employees.show');

Route::middleware(['auth'])
    ->get('/hr/cycles/cycle-types', CycleTypesIndex::class)
    ->name('hr.cycles.cycle-types');

Route::middleware(['auth'])
    ->get('/hr/cycles/cycle-types/{recordId}/view', CycleTypeRecordView::class)
    ->name('hr.cycles.cycle-types.show');

Route::middleware(['auth'])
    ->get('/hr/cycles/cycles', CyclesIndex::class)
    ->name('hr.cycles.cycles');

Route::middleware(['auth'])
    ->get('/hr/cycles/cycles/{recordId}/view', CycleRecordView::class)
    ->name('hr.cycles.cycles.show');

Route::middleware(['auth'])
    ->get('/hr/cycles/cycles/{recordId}/lines', CycleLinesEditor::class)
    ->name('hr.cycles.cycles.edit-lines');

Route::middleware(['auth'])
    ->get('/hr/cycles/transactions', CycleTransactionsIndex::class)
    ->name('hr.cycles.transactions');

Route::middleware(['auth'])
    ->get('/hr/cycles/transactions/{recordId}/view', CycleTransactionRecordView::class)
    ->name('hr.cycles.transactions.show');

Route::middleware(['auth'])
    ->get('/hr/cycles/transactions/{recordId}/review', CycleTransactionReview::class)
    ->name('hr.cycles.transactions.review');
