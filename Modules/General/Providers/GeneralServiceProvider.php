<?php

namespace Modules\General\Providers;

use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\RecordReference\RecordReferenceRegistry;
use Livewire\Livewire;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\Security\Permissions\PermissionsIndex;
use Modules\General\Livewire\Security\Permissions\PermissionsTable;
use Modules\General\Livewire\Security\Rules\RulesIndex;
use Modules\General\Livewire\Security\Rules\RulesTable;
use Modules\General\Livewire\SubModuleWorkspace;
use Modules\General\Livewire\System\Users\UsersIndex;
use Modules\General\Livewire\System\Users\UsersTable;
use Modules\General\Livewire\World\Cities\CitiesIndex;
use Modules\General\Livewire\World\Companies\CompaniesIndex;
use Modules\General\Livewire\World\Companies\CompaniesTable;
use Modules\General\Livewire\World\Countries\CitiesTable;
use Modules\General\Livewire\World\Countries\CountriesIndex;
use Modules\General\Livewire\World\Countries\CountriesTable;
use Modules\General\Livewire\World\Currencies\CurrenciesIndex;
use Modules\General\Livewire\World\Currencies\CurrenciesTable;
use Modules\General\Livewire\World\Languages\LanguagesIndex;
use Modules\General\Livewire\World\Languages\LanguagesTable;
use Modules\General\Livewire\World\People\PeopleIndex;
use Modules\General\Livewire\World\People\PeopleTable;
use Modules\General\Livewire\World\People\PersonPositionsTable;
use Modules\General\Livewire\World\States\StatesIndex;
use Modules\General\Livewire\World\States\StatesTable;
use Modules\General\Livewire\World\Timezones\TimezonesIndex;
use Modules\General\Livewire\World\Timezones\TimezonesTable;
use Modules\General\System\Security\RuleForm;
use Modules\General\System\Security\RuleRecordReferenceProvider;
use Modules\General\System\Security\RuleRecordView as RuleRecordViewDefinition;
use Modules\General\System\SubModuleRecordView;
use Modules\General\System\System\UserForm;
use Modules\General\System\System\UserRecordReferenceProvider;
use Modules\General\System\System\UserRecordView as UserRecordViewDefinition;
use Modules\General\System\World\CityRecordReferenceProvider;
use Modules\General\System\World\CityRecordView as CityRecordViewDefinition;
use Modules\General\System\World\CompanyForm;
use Modules\General\System\World\CompanyRecordReferenceProvider;
use Modules\General\System\World\CompanyRecordView as CompanyRecordViewDefinition;
use Modules\General\System\World\CountryRecordReferenceProvider;
use Modules\General\System\World\CountryRecordView;
use Modules\General\System\World\CurrencyRecordReferenceProvider;
use Modules\General\System\World\CurrencyRecordView as CurrencyRecordViewDefinition;
use Modules\General\System\World\LanguageRecordReferenceProvider;
use Modules\General\System\World\LanguageRecordView as LanguageRecordViewDefinition;
use Modules\General\System\World\PersonForm;
use Modules\General\System\World\PersonRecordReferenceProvider;
use Modules\General\System\World\PersonRecordView as PersonRecordViewDefinition;
use Modules\General\System\World\StateRecordReferenceProvider;
use Modules\General\System\World\StateRecordView as StateRecordViewDefinition;
use Modules\General\System\World\TimezoneRecordReferenceProvider;
use Modules\General\System\World\TimezoneRecordView as TimezoneRecordViewDefinition;
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
        Livewire::component('general.states-index', StatesIndex::class);
        Livewire::component('general.states-table', StatesTable::class);
        Livewire::component('general.cities-index', CitiesIndex::class);
        Livewire::component('general.cities-table', CitiesTable::class);
        Livewire::component('general.currencies-index', CurrenciesIndex::class);
        Livewire::component('general.currencies-table', CurrenciesTable::class);
        Livewire::component('general.timezones-index', TimezonesIndex::class);
        Livewire::component('general.timezones-table', TimezonesTable::class);
        Livewire::component('general.languages-index', LanguagesIndex::class);
        Livewire::component('general.languages-table', LanguagesTable::class);
        Livewire::component('general.companies-index', CompaniesIndex::class);
        Livewire::component('general.companies-table', CompaniesTable::class);
        Livewire::component('general.people-index', PeopleIndex::class);
        Livewire::component('general.people-table', PeopleTable::class);
        Livewire::component('general.person-positions-table', PersonPositionsTable::class);
        Livewire::component('general.rules-index', RulesIndex::class);
        Livewire::component('general.rules-table', RulesTable::class);
        Livewire::component('general.permissions-index', PermissionsIndex::class);
        Livewire::component('general.permissions-table', PermissionsTable::class);
        Livewire::component('general.users-index', UsersIndex::class);
        Livewire::component('general.users-table', UsersTable::class);

        $recordReferenceRegistry = $this->app->make(RecordReferenceRegistry::class);
        $recordReferenceRegistry->register(new CountryRecordReferenceProvider);
        $recordReferenceRegistry->register(new StateRecordReferenceProvider);
        $recordReferenceRegistry->register(new CityRecordReferenceProvider);
        $recordReferenceRegistry->register(new CurrencyRecordReferenceProvider);
        $recordReferenceRegistry->register(new TimezoneRecordReferenceProvider);
        $recordReferenceRegistry->register(new LanguageRecordReferenceProvider);
        $recordReferenceRegistry->register(new CompanyRecordReferenceProvider);
        $recordReferenceRegistry->register(new PersonRecordReferenceProvider);
        $recordReferenceRegistry->register(new RuleRecordReferenceProvider);
        $recordReferenceRegistry->register(new UserRecordReferenceProvider);

        $recordViewRegistry = $this->app->make(RecordViewRegistry::class);
        $recordViewRegistry->register('general.sub-module', SubModuleRecordView::class);
        $recordViewRegistry->register('general.world.country', CountryRecordView::class);
        $recordViewRegistry->register('general.world.state', StateRecordViewDefinition::class);
        $recordViewRegistry->register('general.world.city', CityRecordViewDefinition::class);
        $recordViewRegistry->register('general.world.currency', CurrencyRecordViewDefinition::class);
        $recordViewRegistry->register('general.world.timezone', TimezoneRecordViewDefinition::class);
        $recordViewRegistry->register('general.world.language', LanguageRecordViewDefinition::class);
        $recordViewRegistry->register('general.world.company', CompanyRecordViewDefinition::class);
        $recordViewRegistry->register('general.world.person', PersonRecordViewDefinition::class);
        $recordViewRegistry->register('general.security.rule', RuleRecordViewDefinition::class);
        $recordViewRegistry->register('general.system.user', UserRecordViewDefinition::class);

        $formRegistry = $this->app->make(FormDefinitionRegistry::class);
        $formRegistry->register('general.world.company.create', CompanyForm::class);
        $formRegistry->register('general.world.person.create', PersonForm::class);
        $formRegistry->register('general.security.rule.create', RuleForm::class);
        $formRegistry->register('general.system.user.create', UserForm::class);
    }
}
