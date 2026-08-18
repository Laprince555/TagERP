<?php

use App\Support\ModuleRoute;
use Illuminate\Support\Facades\Route;
use Modules\Finance\Livewire\AccountsPayable\DeductionCategories\DeductionCategoriesIndex;
use Modules\Finance\Livewire\AccountsPayable\DeductionCategories\DeductionCategoryRecordView;
use Modules\Finance\Livewire\AccountsPayable\DeductionGlLinks\DeductionGlLinkRecordView;
use Modules\Finance\Livewire\AccountsPayable\DeductionGlLinks\DeductionGlLinksIndex;
use Modules\Finance\Livewire\AccountsPayable\Deductions\DeductionRecordView;
use Modules\Finance\Livewire\AccountsPayable\Deductions\DeductionsIndex;
use Modules\Finance\Livewire\AccountsPayable\Invoices\ApInvoiceDeductionLineEditor;
use Modules\Finance\Livewire\AccountsPayable\Invoices\ApInvoiceLineEditor;
use Modules\Finance\Livewire\AccountsPayable\Invoices\ApInvoiceRecordView;
use Modules\Finance\Livewire\AccountsPayable\Invoices\ApInvoicesIndex;
use Modules\Finance\Livewire\AccountsPayable\Invoices\ApInvoiceTaxLineEditor;
use Modules\Finance\Livewire\AccountsPayable\InvoiceTypes\InvoiceTypeRecordView;
use Modules\Finance\Livewire\AccountsPayable\InvoiceTypes\InvoiceTypesIndex;
use Modules\Finance\Livewire\AccountsPayable\VendorProfiles\VendorProfileRecordView;
use Modules\Finance\Livewire\AccountsPayable\VendorProfiles\VendorProfilesIndex;
use Modules\Finance\Livewire\AccountsReceivable\CustomerCategories\CustomerCategoriesIndex;
use Modules\Finance\Livewire\AccountsReceivable\CustomerCategories\CustomerCategoryRecordView;
use Modules\Finance\Livewire\AccountsReceivable\CustomerProfiles\CustomerProfileRecordView;
use Modules\Finance\Livewire\AccountsReceivable\CustomerProfiles\CustomerProfilesIndex;
use Modules\Finance\Livewire\CashAndBanks\BankExpenses\BankExpenseRecordView;
use Modules\Finance\Livewire\CashAndBanks\BankExpenses\BankExpensesIndex;
use Modules\Finance\Livewire\CashAndBanks\Banks\Accounts\BankAccountRecordView;
use Modules\Finance\Livewire\CashAndBanks\Banks\Accounts\BankAccountsIndex;
use Modules\Finance\Livewire\CashAndBanks\Banks\BankRecordView;
use Modules\Finance\Livewire\CashAndBanks\Banks\BanksIndex;
use Modules\Finance\Livewire\CashAndBanks\Banks\Checks\CheckRecordView;
use Modules\Finance\Livewire\CashAndBanks\Banks\Checks\ChecksIndex;
use Modules\Finance\Livewire\CashAndBanks\Banks\ChecksBooks\ChecksBookRecordView;
use Modules\Finance\Livewire\CashAndBanks\Banks\ChecksBooks\ChecksBooksIndex;
use Modules\Finance\Livewire\CashAndBanks\Categories\BankCategoriesIndex;
use Modules\Finance\Livewire\CashAndBanks\Categories\BankCategoryRecordView;
use Modules\Finance\Livewire\CashAndBanks\Collections\CollectionRecordView;
use Modules\Finance\Livewire\CashAndBanks\Collections\CollectionsIndex;
use Modules\Finance\Livewire\CashAndBanks\InternalAdjust\InternalAdjustRecordView;
use Modules\Finance\Livewire\CashAndBanks\InternalAdjust\InternalAdjustsIndex;
use Modules\Finance\Livewire\CashAndBanks\PaymentDisbursements\PaymentDisbursementRecordView;
use Modules\Finance\Livewire\CashAndBanks\PaymentDisbursements\PaymentDisbursementsIndex;
use Modules\Finance\Livewire\CashAndBanks\Safes\SafeRecordView;
use Modules\Finance\Livewire\CashAndBanks\Safes\SafesIndex;
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
use Modules\Finance\Livewire\Tax\TaxAdjustments\TaxAdjustmentRecordView;
use Modules\Finance\Livewire\Tax\TaxAdjustments\TaxAdjustmentsIndex;
use Modules\Finance\Livewire\Tax\TaxCategories\TaxCategoriesIndex;
use Modules\Finance\Livewire\Tax\TaxCategories\TaxCategoryRecordView;
use Modules\Finance\Livewire\Tax\Taxes\TaxesIndex;
use Modules\Finance\Livewire\Tax\Taxes\TaxRecordView;
use Modules\Finance\Livewire\Tax\TaxGlLinks\TaxGlLinkRecordView;
use Modules\Finance\Livewire\Tax\TaxGlLinks\TaxGlLinksIndex;
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
    ->get('/finance/accounts-payable/vendor-profiles', VendorProfilesIndex::class)
    ->name('finance.accounts-payable.vendor-profiles');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/vendor-profiles/{recordId}/view', VendorProfileRecordView::class)
    ->name('finance.accounts-payable.vendor-profiles.show');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/invoices', ApInvoicesIndex::class)
    ->name('finance.accounts-payable.invoices');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/invoices/{recordId}/view', ApInvoiceRecordView::class)
    ->name('finance.accounts-payable.invoices.show');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/invoices/{recordId}/lines', ApInvoiceLineEditor::class)
    ->name('finance.accounts-payable.invoices.lines.edit');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/invoices/{recordId}/tax-lines', ApInvoiceTaxLineEditor::class)
    ->name('finance.accounts-payable.invoices.tax-lines.edit');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/invoices/{recordId}/deduction-lines', ApInvoiceDeductionLineEditor::class)
    ->name('finance.accounts-payable.invoices.deduction-lines.edit');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/deduction-categories', DeductionCategoriesIndex::class)
    ->name('finance.accounts-payable.deduction-categories');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/deduction-categories/{recordId}/view', DeductionCategoryRecordView::class)
    ->name('finance.accounts-payable.deduction-categories.show');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/invoice-types', InvoiceTypesIndex::class)
    ->name('finance.accounts-payable.invoice-types');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/invoice-types/{recordId}/view', InvoiceTypeRecordView::class)
    ->name('finance.accounts-payable.invoice-types.show');

Route::middleware(['auth'])
    ->get('/finance/tax-management/tax-categories', TaxCategoriesIndex::class)
    ->name('finance.tax-management.tax-categories');

Route::middleware(['auth'])
    ->get('/finance/tax-management/tax-categories/{recordId}/view', TaxCategoryRecordView::class)
    ->name('finance.tax-management.tax-categories.show');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/deductions', DeductionsIndex::class)
    ->name('finance.accounts-payable.deductions');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/deductions/{recordId}/view', DeductionRecordView::class)
    ->name('finance.accounts-payable.deductions.show');

Route::middleware(['auth'])
    ->get('/finance/tax-management/taxes', TaxesIndex::class)
    ->name('finance.tax-management.taxes');

Route::middleware(['auth'])
    ->get('/finance/tax-management/taxes/{recordId}/view', TaxRecordView::class)
    ->name('finance.tax-management.taxes.show');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/deduction-gl-links', DeductionGlLinksIndex::class)
    ->name('finance.accounts-payable.deduction-gl-links');

Route::middleware(['auth'])
    ->get('/finance/accounts-payable/deduction-gl-links/{recordId}/view', DeductionGlLinkRecordView::class)
    ->name('finance.accounts-payable.deduction-gl-links.show');

Route::middleware(['auth'])
    ->get('/finance/tax-management/tax-gl-links', TaxGlLinksIndex::class)
    ->name('finance.tax-management.tax-gl-links');

Route::middleware(['auth'])
    ->get('/finance/tax-management/tax-gl-links/{recordId}/view', TaxGlLinkRecordView::class)
    ->name('finance.tax-management.tax-gl-links.show');

Route::middleware(['auth'])
    ->get('/finance/tax-management/tax-adjustments', TaxAdjustmentsIndex::class)
    ->name('finance.tax-management.tax-adjustments');

Route::middleware(['auth'])
    ->get('/finance/tax-management/tax-adjustments/{recordId}/view', TaxAdjustmentRecordView::class)
    ->name('finance.tax-management.tax-adjustments.show');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/account-groups', AccountGroupsIndex::class)
    ->name('finance.general-ledger.account-groups');

Route::middleware(['auth'])
    ->get('/finance/general-ledger/account-groups/{recordId}/view', AccountGroupRecordView::class)
    ->name('finance.general-ledger.account-groups.show');

// Cash & Banks - Bank Categories
Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/bank-categories', BankCategoriesIndex::class)
    ->name('finance.cash-and-banks.bank-categories');

Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/bank-categories/{recordId}/view', BankCategoryRecordView::class)
    ->name('finance.cash-and-banks.bank-categories.show');

// Cash & Banks - Banks
Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/banks', BanksIndex::class)
    ->name('finance.cash-and-banks.banks');

Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/banks/{recordId}/view', BankRecordView::class)
    ->name('finance.cash-and-banks.banks.show');

// Cash & Banks - Bank Accounts (SubApplication)
Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/banks/{bankId}/accounts', BankAccountsIndex::class)
    ->name('finance.cash-and-banks.banks.accounts');

Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/banks/{bankId}/accounts/{recordId}/view', BankAccountRecordView::class)
    ->name('finance.cash-and-banks.banks.accounts.show');

// Cash & Banks - Checks Books (SubApplication)
Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/banks/{bankId}/checks-books', ChecksBooksIndex::class)
    ->name('finance.cash-and-banks.banks.checks-books');

Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/banks/{bankId}/checks-books/{recordId}/view', ChecksBookRecordView::class)
    ->name('finance.cash-and-banks.banks.checks-books.show');

// Cash & Banks - Checks (SubApplication)
Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/banks/{bankId}/checks', ChecksIndex::class)
    ->name('finance.cash-and-banks.banks.checks');

Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/banks/{bankId}/checks/{recordId}/view', CheckRecordView::class)
    ->name('finance.cash-and-banks.banks.checks.show');

// Cash & Banks - Safes
Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/safes', SafesIndex::class)
    ->name('finance.cash-and-banks.safes');

Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/safes/{recordId}/view', SafeRecordView::class)
    ->name('finance.cash-and-banks.safes.show');

// Cash & Banks - Internal Adjust
Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/internal-adjust', InternalAdjustsIndex::class)
    ->name('finance.cash-and-banks.internal-adjust');

Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/internal-adjust/{recordId}/view', InternalAdjustRecordView::class)
    ->name('finance.cash-and-banks.internal-adjust.show');

// Cash & Banks - Bank Expenses
Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/bank-expenses', BankExpensesIndex::class)
    ->name('finance.cash-and-banks.bank-expenses');

Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/bank-expenses/{recordId}/view', BankExpenseRecordView::class)
    ->name('finance.cash-and-banks.bank-expenses.show');

// Cash & Banks - Payment Disbursements
Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/payment-disbursements', PaymentDisbursementsIndex::class)
    ->name('finance.cash-and-banks.payment-disbursements');

Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/payment-disbursements/{recordId}/view', PaymentDisbursementRecordView::class)
    ->name('finance.cash-and-banks.payment-disbursements.show');

// Cash & Banks - Collection Requests
Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/collection-requests', CollectionsIndex::class)
    ->name('finance.cash-and-banks.collection-requests');

Route::middleware(['auth'])
    ->get('/finance/cash-and-banks/collection-requests/{recordId}/view', CollectionRecordView::class)
    ->name('finance.cash-and-banks.collection-requests.show');

// Accounts Receivable - Customer Categories
Route::middleware(['auth'])
    ->get('/finance/accounts-receivable/customer-categories', CustomerCategoriesIndex::class)
    ->name('finance.accounts-receivable.customer-categories');

Route::middleware(['auth'])
    ->get('/finance/accounts-receivable/customer-categories/{recordId}/view', CustomerCategoryRecordView::class)
    ->name('finance.accounts-receivable.customer-categories.show');

// Accounts Receivable - Customer Profiles
Route::middleware(['auth'])
    ->get('/finance/accounts-receivable/customer-profiles', CustomerProfilesIndex::class)
    ->name('finance.accounts-receivable.customer-profiles');

Route::middleware(['auth'])
    ->get('/finance/accounts-receivable/customer-profiles/{recordId}/view', CustomerProfileRecordView::class)
    ->name('finance.accounts-receivable.customer-profiles.show');
