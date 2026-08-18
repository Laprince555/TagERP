<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Finance\Database\Seeders\GeneralLedger\ApplicationsSeeder as FinanceGeneralLedgerApplicationsSeeder;
use Modules\Finance\Database\Seeders\System\SubModulesSeeder as FinanceSubModulesSeeder;
use Modules\Finance\Livewire\GeneralLedger\Journals\JournalRecordView;
use Modules\Finance\Models\GeneralLedger\Chart;
use Modules\Finance\Models\GeneralLedger\Journal;
use Modules\Finance\Models\GeneralLedger\JournalBook;
use Modules\Finance\Models\GeneralLedger\Ledger;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Models\World\Currency;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role as SpatieRoleBase;

it('writes an activity log entry when a header action runs', function () {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new FinanceSubModulesSeeder)->run();
    (new FinanceGeneralLedgerApplicationsSeeder)->run();

    $actor = User::factory()->create();
    $actor->assignRole(SpatieRoleBase::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));
    $this->actingAs($actor);

    $chart = Chart::factory()->create(['levels_count' => 5]);
    $ledger = Ledger::factory()->create([
        'chart_id' => $chart->id,
        'base_currency_id' => Currency::factory()->create()->id,
    ]);

    $journal = Journal::factory()->create([
        'ledger_id' => $ledger->id,
        'journal_book_id' => JournalBook::factory()->create(['sequence_prefix' => 'JV'])->id,
    ]);

    Livewire::test(JournalRecordView::class, ['recordId' => $journal->id])
        ->call('runAction', 'delete');

    $entry = Activity::query()->latest('id')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->causer_id)->toBe($actor->id)
        ->and(($entry->properties['action'] ?? null))->toBe('delete')
        ->and($entry->log_name)->toBe(Journal::APPLICATION_CODE);
});
