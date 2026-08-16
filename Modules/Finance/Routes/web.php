<?php

use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\Finance\Livewire\GeneralLedger\AccountCategories\AccountCategoriesIndex;
use Modules\Finance\Livewire\GeneralLedger\AccountCategories\AccountCategoryRecordView;
use Modules\Finance\Livewire\GeneralLedger\AccountGroups\AccountGroupRecordView;
use Modules\Finance\Livewire\GeneralLedger\AccountGroups\AccountGroupsIndex;
use Modules\Finance\Livewire\GeneralLedger\Accounts\AccountRecordView;
use Modules\Finance\Livewire\GeneralLedger\Accounts\AccountsIndex;
use Modules\Finance\Livewire\GeneralLedger\Charts\ChartRecordView;
use Modules\Finance\Livewire\GeneralLedger\Charts\ChartsIndex;
use Modules\Finance\Livewire\GeneralLedger\CostCenters\CostCenterRecordView;
use Modules\Finance\Livewire\GeneralLedger\CostCenters\CostCentersIndex;
use Modules\Finance\Livewire\GeneralLedger\ExchangeRates\ExchangeRateRecordView;
use Modules\Finance\Livewire\GeneralLedger\ExchangeRates\ExchangeRatesIndex;
use Modules\Finance\Livewire\GeneralLedger\FiscalYears\FiscalYearRecordView;
use Modules\Finance\Livewire\GeneralLedger\FiscalYears\FiscalYearsIndex;
use Modules\Finance\Livewire\GeneralLedger\JournalBooks\JournalBookRecordView;
use Modules\Finance\Livewire\GeneralLedger\JournalBooks\JournalBooksIndex;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalEditor;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalRecordView;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalsIndex;
use Modules\Finance\Livewire\GeneralLedger\Ledgers\LedgerRecordView;
use Modules\Finance\Livewire\GeneralLedger\Ledgers\LedgersIndex;
use Modules\General\Livewire\ModuleWorkspace;
use Modules\General\Livewire\SubModuleWorkspace;

ModuleRoute::registerIndex('finance', '/finance', ModuleWorkspace::class);
ModuleRoute::registerSubModules('finance', '/finance', SubModuleWorkspace::class);

Route::middleware(['auth'])
    ->get('/finance/general-ledger/account-categories', AccountCategoriesIndex::class)
    ->name('finance.general-ledger.account-categories');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/account-categories/{recordId}/view', AccountCategoryRecordView::class)
    ->name('finance.general-ledger.account-categories.show');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/accounts', AccountsIndex::class)
    ->name('finance.general-ledger.accounts');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/accounts/{recordId}/view', AccountRecordView::class)
    ->name('finance.general-ledger.accounts.show');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/charts', ChartsIndex::class)
    ->name('finance.general-ledger.charts');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/charts/{recordId}/view', ChartRecordView::class)
    ->name('finance.general-ledger.charts.show');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/exchange-rates', ExchangeRatesIndex::class)
    ->name('finance.general-ledger.exchange-rates');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/exchange-rates/{recordId}/view', ExchangeRateRecordView::class)
    ->name('finance.general-ledger.exchange-rates.show');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/fiscal-years', FiscalYearsIndex::class)
    ->name('finance.general-ledger.fiscal-years');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/fiscal-years/{recordId}/view', FiscalYearRecordView::class)
    ->name('finance.general-ledger.fiscal-years.show');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/ledgers', LedgersIndex::class)
    ->name('finance.general-ledger.ledgers');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/ledgers/{recordId}/view', LedgerRecordView::class)
    ->name('finance.general-ledger.ledgers.show');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/journal-books', JournalBooksIndex::class)
    ->name('finance.general-ledger.journal-books');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/journal-books/{recordId}/view', JournalBookRecordView::class)
    ->name('finance.general-ledger.journal-books.show');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/journals', JournalsIndex::class)
    ->name('finance.general-ledger.journals');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/journals/{recordId}/view', JournalRecordView::class)
    ->name('finance.general-ledger.journals.show');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/journals/{recordId}/edit', JournalEditor::class)
    ->name('finance.general-ledger.journals.edit');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/cost-centers', CostCentersIndex::class)
    ->name('finance.general-ledger.cost-centers');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/cost-centers/{recordId}/view', CostCenterRecordView::class)
    ->name('finance.general-ledger.cost-centers.show');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/account-groups', AccountGroupsIndex::class)
    ->name('finance.general-ledger.account-groups');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/account-groups/{recordId}/view', AccountGroupRecordView::class)
    ->name('finance.general-ledger.account-groups.show');
