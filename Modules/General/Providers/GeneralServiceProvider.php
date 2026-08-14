<?php

namespace Modules\General\Providers;

use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\RecordReference\RecordReferenceRegistry;
use Livewire\Livewire;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;
use Modules\General\Livewire\World\Countries\CountriesIndex;
use Modules\General\Livewire\World\Countries\CountriesTable;
use Modules\General\System\SubModuleRecordView;
use Modules\General\System\World\CountryRecordReferenceProvider;
use Modules\General\System\World\CountryRecordView;
use Nwidart\Modules\Support\ModuleServiceProvider;

class GeneralServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'General';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'general';

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
     * Module classes live outside Livewire's discovered namespaces, so the shared
     * workspace pages are aliased explicitly to keep their component names stable
     * across the initial render and subsequent Livewire requests.
     */
    public function boot(): void
    {
        parent::boot();

        Livewire::component('general.module-workspace', ModuleWorkspace::class);
        Livewire::component('general.sub-module-workspace', SubModuleWorkspace::class);
        Livewire::component('general.countries-index', CountriesIndex::class);
        Livewire::component('general.countries-table', CountriesTable::class);

        $this->app->make(RecordReferenceRegistry::class)
            ->register(new CountryRecordReferenceProvider);

        $recordViewRegistry = $this->app->make(RecordViewRegistry::class);
        $recordViewRegistry->register('general.sub-module', SubModuleRecordView::class);
        $recordViewRegistry->register('general.world.country', CountryRecordView::class);
    }
}
