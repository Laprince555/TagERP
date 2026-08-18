<?php

namespace Modules\HR\Providers;

use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\RecordReference\RecordReferenceRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Modules\HR\Livewire\Cycles\Cycles\CycleLinesEditor;
use Modules\HR\Livewire\Cycles\Cycles\CycleLinesTable;
use Modules\HR\Livewire\Cycles\Cycles\CyclesIndex;
use Modules\HR\Livewire\Cycles\Cycles\CyclesTable;
use Modules\HR\Livewire\Cycles\CycleTypes\CycleTypesIndex;
use Modules\HR\Livewire\Cycles\CycleTypes\CycleTypesTable;
use Modules\HR\Livewire\Cycles\Transactions\CycleTransactionLinesTable;
use Modules\HR\Livewire\Cycles\Transactions\CycleTransactionReview;
use Modules\HR\Livewire\Cycles\Transactions\CycleTransactionsIndex;
use Modules\HR\Livewire\Cycles\Transactions\CycleTransactionsTable;
use Modules\HR\Livewire\EmployeeManagement\Employees\EmployeesIndex;
use Modules\HR\Livewire\EmployeeManagement\Employees\EmployeesTable;
use Modules\HR\Livewire\OrganizationStructure\Branches\BranchesIndex;
use Modules\HR\Livewire\OrganizationStructure\Branches\BranchesTable;
use Modules\HR\Livewire\OrganizationStructure\Departments\DepartmentEntitiesTable;
use Modules\HR\Livewire\OrganizationStructure\Departments\DepartmentsIndex;
use Modules\HR\Livewire\OrganizationStructure\Departments\DepartmentsTable;
use Modules\HR\Livewire\OrganizationStructure\Entities\EntitiesIndex;
use Modules\HR\Livewire\OrganizationStructure\Entities\EntitiesTable;
use Modules\HR\Livewire\OrganizationStructure\JobGrades\JobGradesIndex;
use Modules\HR\Livewire\OrganizationStructure\JobGrades\JobGradesTable;
use Modules\HR\Livewire\OrganizationStructure\JobTitles\JobTitleGradesTable;
use Modules\HR\Livewire\OrganizationStructure\JobTitles\JobTitlesIndex;
use Modules\HR\Livewire\OrganizationStructure\JobTitles\JobTitlesTable;
use Modules\HR\System\Cycles\CycleForm;
use Modules\HR\System\Cycles\CycleRecordReferenceProvider;
use Modules\HR\System\Cycles\CycleRecordView as CycleRecordViewDefinition;
use Modules\HR\System\Cycles\CycleTransactionRecordView as CycleTransactionRecordViewDefinition;
use Modules\HR\System\Cycles\CycleTypeForm;
use Modules\HR\System\Cycles\CycleTypeRecordReferenceProvider;
use Modules\HR\System\Cycles\CycleTypeRecordView as CycleTypeRecordViewDefinition;
use Modules\HR\System\EmployeeManagement\EmployeeForm;
use Modules\HR\System\EmployeeManagement\EmployeeRecordReferenceProvider;
use Modules\HR\System\EmployeeManagement\EmployeeRecordView as EmployeeRecordViewDefinition;
use Modules\HR\System\OrganizationStructure\BranchForm;
use Modules\HR\System\OrganizationStructure\BranchRecordReferenceProvider;
use Modules\HR\System\OrganizationStructure\BranchRecordView as BranchRecordViewDefinition;
use Modules\HR\System\OrganizationStructure\DepartmentForm;
use Modules\HR\System\OrganizationStructure\DepartmentRecordReferenceProvider;
use Modules\HR\System\OrganizationStructure\DepartmentRecordView as DepartmentRecordViewDefinition;
use Modules\HR\System\OrganizationStructure\EntityForm;
use Modules\HR\System\OrganizationStructure\EntityRecordReferenceProvider;
use Modules\HR\System\OrganizationStructure\EntityRecordView as EntityRecordViewDefinition;
use Modules\HR\System\OrganizationStructure\JobGradeForm;
use Modules\HR\System\OrganizationStructure\JobGradeRecordReferenceProvider;
use Modules\HR\System\OrganizationStructure\JobGradeRecordView as JobGradeRecordViewDefinition;
use Modules\HR\System\OrganizationStructure\JobTitleForm;
use Modules\HR\System\OrganizationStructure\JobTitleRecordReferenceProvider;
use Modules\HR\System\OrganizationStructure\JobTitleRecordView as JobTitleRecordViewDefinition;
use Nwidart\Modules\Support\ModuleServiceProvider;

class HRServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'HR';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'hr';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('hr.entities-index', EntitiesIndex::class);
        Livewire::component('hr.entities-table', EntitiesTable::class);
        Livewire::component('hr.branches-index', BranchesIndex::class);
        Livewire::component('hr.branches-table', BranchesTable::class);
        Livewire::component('hr.departments-index', DepartmentsIndex::class);
        Livewire::component('hr.departments-table', DepartmentsTable::class);
        Livewire::component('hr.department-entities-table', DepartmentEntitiesTable::class);
        Livewire::component('hr.job-grades-index', JobGradesIndex::class);
        Livewire::component('hr.job-grades-table', JobGradesTable::class);
        Livewire::component('hr.job-titles-index', JobTitlesIndex::class);
        Livewire::component('hr.job-titles-table', JobTitlesTable::class);
        Livewire::component('hr.job-title-grades-table', JobTitleGradesTable::class);
        Livewire::component('hr.employees-index', EmployeesIndex::class);
        Livewire::component('hr.employees-table', EmployeesTable::class);

        Livewire::component('hr.cycle-types-index', CycleTypesIndex::class);
        Livewire::component('hr.cycle-types-table', CycleTypesTable::class);
        Livewire::component('hr.cycles-index', CyclesIndex::class);
        Livewire::component('hr.cycles-table', CyclesTable::class);
        Livewire::component('hr.cycle-lines-table', CycleLinesTable::class);
        Livewire::component('hr.cycle-lines-editor', CycleLinesEditor::class);
        Livewire::component('hr.cycle-transactions-index', CycleTransactionsIndex::class);
        Livewire::component('hr.cycle-transactions-table', CycleTransactionsTable::class);
        Livewire::component('hr.cycle-transaction-lines-table', CycleTransactionLinesTable::class);
        Livewire::component('hr.cycle-transaction-review', CycleTransactionReview::class);

        $recordReferenceRegistry = $this->app->make(RecordReferenceRegistry::class);
        $recordReferenceRegistry->register(new EntityRecordReferenceProvider);
        $recordReferenceRegistry->register(new BranchRecordReferenceProvider);
        $recordReferenceRegistry->register(new DepartmentRecordReferenceProvider);
        $recordReferenceRegistry->register(new JobGradeRecordReferenceProvider);
        $recordReferenceRegistry->register(new JobTitleRecordReferenceProvider);
        $recordReferenceRegistry->register(new EmployeeRecordReferenceProvider);
        $recordReferenceRegistry->register(new CycleTypeRecordReferenceProvider);
        $recordReferenceRegistry->register(new CycleRecordReferenceProvider);

        $recordViewRegistry = $this->app->make(RecordViewRegistry::class);
        $recordViewRegistry->register('hr.organization-structure.entity', EntityRecordViewDefinition::class);
        $recordViewRegistry->register('hr.organization-structure.branch', BranchRecordViewDefinition::class);
        $recordViewRegistry->register('hr.organization-structure.department', DepartmentRecordViewDefinition::class);
        $recordViewRegistry->register('hr.organization-structure.job-grade', JobGradeRecordViewDefinition::class);
        $recordViewRegistry->register('hr.organization-structure.job-title', JobTitleRecordViewDefinition::class);
        $recordViewRegistry->register('hr.employee-management.employee', EmployeeRecordViewDefinition::class);
        $recordViewRegistry->register('hr.cycles.cycle-type', CycleTypeRecordViewDefinition::class);
        $recordViewRegistry->register('hr.cycles.cycle', CycleRecordViewDefinition::class);
        $recordViewRegistry->register('hr.cycles.transaction', CycleTransactionRecordViewDefinition::class);

        $formRegistry = $this->app->make(FormDefinitionRegistry::class);
        $formRegistry->register('hr.organization-structure.entity.create', EntityForm::class);
        $formRegistry->register('hr.organization-structure.branch.create', BranchForm::class);
        $formRegistry->register('hr.organization-structure.department.create', DepartmentForm::class);
        $formRegistry->register('hr.organization-structure.job-grade.create', JobGradeForm::class);
        $formRegistry->register('hr.organization-structure.job-title.create', JobTitleForm::class);
        $formRegistry->register('hr.employee-management.employee.create', EmployeeForm::class);
        $formRegistry->register('hr.cycles.cycle-type.create', CycleTypeForm::class);
        $formRegistry->register('hr.cycles.cycle.create', CycleForm::class);
    }
}
