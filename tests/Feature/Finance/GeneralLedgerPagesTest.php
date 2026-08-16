<?php

use App\Models\User;
use Modules\Finance\Database\Seeders\GeneralLedger\ApplicationsSeeder as FinanceGeneralLedgerApplicationsSeeder;
use Modules\Finance\Database\Seeders\System\SubModulesSeeder as FinanceSubModulesSeeder;
use Modules\Finance\Livewire\GeneralLedger\AccountCategories\AccountCategoriesTable;
use Modules\Finance\Livewire\GeneralLedger\Accounts\AccountsTable;
use Modules\Finance\Livewire\GeneralLedger\Charts\ChartsTable;
use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\AccountGroup;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\CostCenter;
use Modules\Finance\Models\GeneralLedger\ExchangeRate;
use Modules\Finance\Models\GeneralLedger\FiscalYear;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\System\Application;

beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new FinanceSubModulesSeeder)->run();
    (new FinanceGeneralLedgerApplicationsSeeder)->run();

    $this->actingAs(User::factory()->create());
});

it('renders the account categories index', function (): void {
    $this->get(route('finance.general-ledger.account-categories'))
        ->assertSuccessful()
        ->assertSee('fin-gl-cat')
        ->assertSeeLivewire(AccountCategoriesTable::class);
});

it('renders the accounts index', function (): void {
    Account::factory()->create(['name' => 'Petty Cash', 'number' => '110101']);

    $this->get(route('finance.general-ledger.accounts'))
        ->assertSuccessful()
        ->assertSee('fin-gl-acc')
        ->assertSeeLivewire(AccountsTable::class);
});

it('renders the charts index', function (): void {
    Chart::factory()->create(['name' => 'Statutory Chart']);

    $this->get(route('finance.general-ledger.charts'))
        ->assertSuccessful()
        ->assertSee('fin-gl-coa')
        ->assertSeeLivewire(ChartsTable::class);
});

it('renders the exchange rates index', function (): void {
    ExchangeRate::factory()->create();

    $this->get(route('finance.general-ledger.exchange-rates'))
        ->assertSuccessful()
        ->assertSee('fin-gl-rat');
});

it('renders the fiscal years index', function (): void {
    FiscalYear::factory()->create(['name' => 'FY 2026']);

    $this->get(route('finance.general-ledger.fiscal-years'))
        ->assertSuccessful()
        ->assertSee('fin-gl-fyr');
});

it('renders the ledgers index', function (): void {
    Ledger::factory()->create(['name' => 'Statutory Ledger']);

    $this->get(route('finance.general-ledger.ledgers'))
        ->assertSuccessful()
        ->assertSee('fin-gl-led');
});

it('renders the journal books index', function (): void {
    JournalBook::factory()->create(['name' => 'Receipt Voucher', 'sequence_prefix' => 'REC']);

    $this->get(route('finance.general-ledger.journal-books'))
        ->assertSuccessful()
        ->assertSee('fin-gl-bok');
});

it('denies access to an inactive application', function (): void {
    Application::where('code', 'fin-gl-acc')->update(['is_active' => false]);

    $this->get(route('finance.general-ledger.accounts'))->assertNotFound();
});

it('renders the record view of an account', function (): void {
    $account = Account::factory()->create(['name' => 'Petty Cash', 'number' => '110101']);

    $this->get(route('finance.general-ledger.accounts.show', ['recordId' => $account->id]))
        ->assertSuccessful()
        ->assertSee('Petty Cash');
});

it('renders the record view of a fiscal year with its periods', function (): void {
    $year = FiscalYear::factory()->create(['name' => 'FY 2026']);
    $year->generatePeriods(12);

    $this->get(route('finance.general-ledger.fiscal-years.show', ['recordId' => $year->id]))
        ->assertSuccessful()
        ->assertSee('FY 2026');
});

it('renders the record view of a ledger', function (): void {
    $ledger = Ledger::factory()->create(['name' => 'Statutory Ledger']);

    $this->get(route('finance.general-ledger.ledgers.show', ['recordId' => $ledger->id]))
        ->assertSuccessful()
        ->assertSee('Statutory Ledger');
});

it('renders the journals index', function (): void {
    $this->get(route('finance.general-ledger.journals'))
        ->assertSuccessful()
        ->assertSee('fin-gl-jou');
});

it('renders the record view of a journal with its lines tab', function (): void {
    $journal = Journal::factory()->create();

    $this->get(route('finance.general-ledger.journals.show', ['recordId' => $journal->id]))
        ->assertSuccessful()
        ->assertSee($journal->code);
});

it('renders the cost centres index', function (): void {
    CostCenter::factory()->create(['name' => 'Operations', 'number' => 'CC-100']);

    $this->get(route('finance.general-ledger.cost-centers'))
        ->assertSuccessful()
        ->assertSee('fin-gl-cct');
});

it('renders the account groups index', function (): void {
    AccountGroup::factory()->create(['name' => 'Treasury Accounts']);

    $this->get(route('finance.general-ledger.account-groups'))
        ->assertSuccessful()
        ->assertSee('fin-gl-agr');
});

it('renders the record view of a cost centre', function (): void {
    $centre = CostCenter::factory()->create(['name' => 'Operations', 'number' => 'CC-100']);

    $this->get(route('finance.general-ledger.cost-centers.show', ['recordId' => $centre->id]))
        ->assertSuccessful()
        ->assertSee('Operations');
});

it('renders the record view of an account group with its accounts tab', function (): void {
    $group = AccountGroup::factory()->create(['name' => 'Treasury Accounts']);

    $this->get(route('finance.general-ledger.account-groups.show', ['recordId' => $group->id]))
        ->assertSuccessful()
        ->assertSee('Treasury Accounts');
});
