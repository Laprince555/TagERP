<?php

namespace Modules\Finance\Providers;

use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\RecordReference\RecordReferenceRegistry;
use Livewire\Livewire;
use Modules\Finance\Livewire\GeneralLedger\AccountCategories\AccountCategoriesIndex;
use Modules\Finance\Livewire\GeneralLedger\AccountCategories\AccountCategoriesTable;
use Modules\Finance\Livewire\GeneralLedger\AccountGroups\AccountGroupAccountsTable;
use Modules\Finance\Livewire\GeneralLedger\AccountGroups\AccountGroupsIndex;
use Modules\Finance\Livewire\GeneralLedger\AccountGroups\AccountGroupsTable;
use Modules\Finance\Livewire\GeneralLedger\Accounts\AccountChildrenTable;
use Modules\Finance\Livewire\GeneralLedger\Accounts\AccountsIndex;
use Modules\Finance\Livewire\GeneralLedger\Accounts\AccountsTable;
use Modules\Finance\Livewire\GeneralLedger\Charts\ChartAccountsTable;
use Modules\Finance\Livewire\GeneralLedger\Charts\ChartsIndex;
use Modules\Finance\Livewire\GeneralLedger\Charts\ChartsTable;
use Modules\Finance\Livewire\GeneralLedger\CostCenters\CostCentersIndex;
use Modules\Finance\Livewire\GeneralLedger\CostCenters\CostCentersTable;
use Modules\Finance\Livewire\GeneralLedger\ExchangeRates\ExchangeRatesIndex;
use Modules\Finance\Livewire\GeneralLedger\ExchangeRates\ExchangeRatesTable;
use Modules\Finance\Livewire\GeneralLedger\FiscalYears\FiscalPeriodsTable;
use Modules\Finance\Livewire\GeneralLedger\FiscalYears\FiscalYearsIndex;
use Modules\Finance\Livewire\GeneralLedger\FiscalYears\FiscalYearsTable;
use Modules\Finance\Livewire\GeneralLedger\JournalBooks\JournalBookLedgersTable;
use Modules\Finance\Livewire\GeneralLedger\JournalBooks\JournalBooksIndex;
use Modules\Finance\Livewire\GeneralLedger\JournalBooks\JournalBooksTable;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalEditor;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalLinesTable;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalsIndex;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalsTable;
use Modules\Finance\Livewire\GeneralLedger\Ledgers\LedgersIndex;
use Modules\Finance\Livewire\GeneralLedger\Ledgers\LedgersTable;
use Modules\Finance\System\GeneralLedger\AccountCategoryForm;
use Modules\Finance\System\GeneralLedger\AccountCategoryRecordReferenceProvider;
use Modules\Finance\System\GeneralLedger\AccountCategoryRecordView as AccountCategoryRecordViewDefinition;
use Modules\Finance\System\GeneralLedger\AccountForm;
use Modules\Finance\System\GeneralLedger\AccountGroupForm;
use Modules\Finance\System\GeneralLedger\AccountGroupRecordReferenceProvider;
use Modules\Finance\System\GeneralLedger\AccountGroupRecordView as AccountGroupRecordViewDefinition;
use Modules\Finance\System\GeneralLedger\AccountRecordReferenceProvider;
use Modules\Finance\System\GeneralLedger\AccountRecordView as AccountRecordViewDefinition;
use Modules\Finance\System\GeneralLedger\ChartForm;
use Modules\Finance\System\GeneralLedger\ChartRecordReferenceProvider;
use Modules\Finance\System\GeneralLedger\ChartRecordView as ChartRecordViewDefinition;
use Modules\Finance\System\GeneralLedger\CostCenterForm;
use Modules\Finance\System\GeneralLedger\CostCenterRecordReferenceProvider;
use Modules\Finance\System\GeneralLedger\CostCenterRecordView as CostCenterRecordViewDefinition;
use Modules\Finance\System\GeneralLedger\ExchangeRateForm;
use Modules\Finance\System\GeneralLedger\ExchangeRateRecordReferenceProvider;
use Modules\Finance\System\GeneralLedger\ExchangeRateRecordView as ExchangeRateRecordViewDefinition;
use Modules\Finance\System\GeneralLedger\FiscalYearForm;
use Modules\Finance\System\GeneralLedger\FiscalYearRecordReferenceProvider;
use Modules\Finance\System\GeneralLedger\FiscalYearRecordView as FiscalYearRecordViewDefinition;
use Modules\Finance\System\GeneralLedger\JournalBookForm;
use Modules\Finance\System\GeneralLedger\JournalBookRecordReferenceProvider;
use Modules\Finance\System\GeneralLedger\JournalBookRecordView as JournalBookRecordViewDefinition;
use Modules\Finance\System\GeneralLedger\JournalForm;
use Modules\Finance\System\GeneralLedger\JournalRecordReferenceProvider;
use Modules\Finance\System\GeneralLedger\JournalRecordView as JournalRecordViewDefinition;
use Modules\Finance\System\GeneralLedger\LedgerForm;
use Modules\Finance\System\GeneralLedger\LedgerRecordReferenceProvider;
use Modules\Finance\System\GeneralLedger\LedgerRecordView as LedgerRecordViewDefinition;
use Nwidart\Modules\Support\ModuleServiceProvider;

class FinanceServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Finance';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'finance';

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

        Livewire::component('finance.account-categories-index', AccountCategoriesIndex::class);
        Livewire::component('finance.account-categories-table', AccountCategoriesTable::class);
        Livewire::component('finance.accounts-index', AccountsIndex::class);
        Livewire::component('finance.accounts-table', AccountsTable::class);
        Livewire::component('finance.account-children-table', AccountChildrenTable::class);
        Livewire::component('finance.charts-index', ChartsIndex::class);
        Livewire::component('finance.charts-table', ChartsTable::class);
        Livewire::component('finance.chart-accounts-table', ChartAccountsTable::class);
        Livewire::component('finance.exchange-rates-index', ExchangeRatesIndex::class);
        Livewire::component('finance.exchange-rates-table', ExchangeRatesTable::class);
        Livewire::component('finance.fiscal-years-index', FiscalYearsIndex::class);
        Livewire::component('finance.fiscal-years-table', FiscalYearsTable::class);
        Livewire::component('finance.fiscal-periods-table', FiscalPeriodsTable::class);
        Livewire::component('finance.ledgers-index', LedgersIndex::class);
        Livewire::component('finance.ledgers-table', LedgersTable::class);
        Livewire::component('finance.journal-books-index', JournalBooksIndex::class);
        Livewire::component('finance.journal-books-table', JournalBooksTable::class);
        Livewire::component('finance.journal-book-ledgers-table', JournalBookLedgersTable::class);
        Livewire::component('finance.journals-index', JournalsIndex::class);
        Livewire::component('finance.journals-table', JournalsTable::class);
        Livewire::component('finance.journal-lines-table', JournalLinesTable::class);
        Livewire::component('finance.journal-editor', JournalEditor::class);
        Livewire::component('finance.cost-centers-index', CostCentersIndex::class);
        Livewire::component('finance.cost-centers-table', CostCentersTable::class);
        Livewire::component('finance.account-groups-index', AccountGroupsIndex::class);
        Livewire::component('finance.account-groups-table', AccountGroupsTable::class);
        Livewire::component('finance.account-group-accounts-table', AccountGroupAccountsTable::class);

        $recordReferenceRegistry = $this->app->make(RecordReferenceRegistry::class);
        $recordReferenceRegistry->register(new AccountCategoryRecordReferenceProvider);
        $recordReferenceRegistry->register(new AccountRecordReferenceProvider);
        $recordReferenceRegistry->register(new ChartRecordReferenceProvider);
        $recordReferenceRegistry->register(new ExchangeRateRecordReferenceProvider);
        $recordReferenceRegistry->register(new FiscalYearRecordReferenceProvider);
        $recordReferenceRegistry->register(new LedgerRecordReferenceProvider);
        $recordReferenceRegistry->register(new JournalBookRecordReferenceProvider);
        $recordReferenceRegistry->register(new JournalRecordReferenceProvider);
        $recordReferenceRegistry->register(new CostCenterRecordReferenceProvider);
        $recordReferenceRegistry->register(new AccountGroupRecordReferenceProvider);

        $recordViewRegistry = $this->app->make(RecordViewRegistry::class);
        $recordViewRegistry->register('finance.general-ledger.account-category', AccountCategoryRecordViewDefinition::class);
        $recordViewRegistry->register('finance.general-ledger.account', AccountRecordViewDefinition::class);
        $recordViewRegistry->register('finance.general-ledger.chart', ChartRecordViewDefinition::class);
        $recordViewRegistry->register('finance.general-ledger.exchange-rate', ExchangeRateRecordViewDefinition::class);
        $recordViewRegistry->register('finance.general-ledger.fiscal-year', FiscalYearRecordViewDefinition::class);
        $recordViewRegistry->register('finance.general-ledger.ledger', LedgerRecordViewDefinition::class);
        $recordViewRegistry->register('finance.general-ledger.journal-book', JournalBookRecordViewDefinition::class);
        $recordViewRegistry->register('finance.general-ledger.journal', JournalRecordViewDefinition::class);
        $recordViewRegistry->register('finance.general-ledger.cost-center', CostCenterRecordViewDefinition::class);
        $recordViewRegistry->register('finance.general-ledger.account-group', AccountGroupRecordViewDefinition::class);

        $formRegistry = $this->app->make(FormDefinitionRegistry::class);
        $formRegistry->register('finance.general-ledger.account-category.create', AccountCategoryForm::class);
        $formRegistry->register('finance.general-ledger.account.create', AccountForm::class);
        $formRegistry->register('finance.general-ledger.chart.create', ChartForm::class);
        $formRegistry->register('finance.general-ledger.exchange-rate.create', ExchangeRateForm::class);
        $formRegistry->register('finance.general-ledger.fiscal-year.create', FiscalYearForm::class);
        $formRegistry->register('finance.general-ledger.ledger.create', LedgerForm::class);
        $formRegistry->register('finance.general-ledger.journal-book.create', JournalBookForm::class);
        $formRegistry->register('finance.general-ledger.journal.create', JournalForm::class);
        $formRegistry->register('finance.general-ledger.cost-center.create', CostCenterForm::class);
        $formRegistry->register('finance.general-ledger.account-group.create', AccountGroupForm::class);
    }
}
