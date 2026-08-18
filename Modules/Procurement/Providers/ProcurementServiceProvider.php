<?php

namespace Modules\Procurement\Providers;

use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\RecordReference\RecordReferenceRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Modules\Procurement\Livewire\Vendors\Vendors\VendorsIndex;
use Modules\Procurement\Livewire\Vendors\Vendors\VendorsTable;
use Modules\Procurement\System\Vendors\VendorForm;
use Modules\Procurement\System\Vendors\VendorRecordReferenceProvider;
use Modules\Procurement\System\Vendors\VendorRecordView as VendorRecordViewDefinition;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProcurementServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Procurement';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'procurement';

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

        Livewire::component('procurement.vendors-index', VendorsIndex::class);
        Livewire::component('procurement.vendors-table', VendorsTable::class);

        $recordReferenceRegistry = $this->app->make(RecordReferenceRegistry::class);
        $recordReferenceRegistry->register(new VendorRecordReferenceProvider);

        $recordViewRegistry = $this->app->make(RecordViewRegistry::class);
        $recordViewRegistry->register('procurement.vendor-management.vendor', VendorRecordViewDefinition::class);

        $formRegistry = $this->app->make(FormDefinitionRegistry::class);
        $formRegistry->register('procurement.vendor-management.vendor.create', VendorForm::class);
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
