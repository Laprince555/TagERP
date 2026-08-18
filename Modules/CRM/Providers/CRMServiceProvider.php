<?php

namespace Modules\CRM\Providers;

use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\RecordReference\RecordReferenceRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Modules\CRM\Livewire\Customers\CustomersIndex;
use Modules\CRM\Livewire\Customers\CustomersTable;
use Modules\CRM\System\Customers\CustomerForm;
use Modules\CRM\System\Customers\CustomerRecordReferenceProvider;
use Modules\CRM\System\Customers\CustomerRecordView as CustomerRecordViewDefinition;
use Nwidart\Modules\Support\ModuleServiceProvider;

class CRMServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'CRM';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'crm';

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

    public function boot(): void
    {
        parent::boot();

        Livewire::component('crm.customers-index', CustomersIndex::class);
        Livewire::component('crm.customers-table', CustomersTable::class);

        $recordReferenceRegistry = $this->app->make(RecordReferenceRegistry::class);
        $recordReferenceRegistry->register(new CustomerRecordReferenceProvider);

        $recordViewRegistry = $this->app->make(RecordViewRegistry::class);
        $recordViewRegistry->register('crm.customer-management.customer', CustomerRecordViewDefinition::class);

        $formRegistry = $this->app->make(FormDefinitionRegistry::class);
        $formRegistry->register('crm.customer-management.customer.create', CustomerForm::class);
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
