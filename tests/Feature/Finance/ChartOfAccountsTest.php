<?php

use Modules\Finance\Models\GeneralLedger\Account;
use Modules\Finance\Models\GeneralLedger\AccountCategory;
use Modules\Finance\Models\GeneralLedger\AccountNature;
use Modules\Finance\Models\GeneralLedger\Chart;

use function Pest\Laravel\assertDatabaseCount;

it('derives normal balance and statement from the account nature', function () {
    $asset = AccountCategory::factory()->nature(AccountNature::Asset)->create();
    $revenue = AccountCategory::factory()->nature(AccountNature::Revenue)->create();

    expect($asset->normal_balance->value)->toBe('debit')
        ->and($asset->statement->value)->toBe('balance_sheet')
        ->and($revenue->normal_balance->value)->toBe('credit')
        ->and($revenue->statement->value)->toBe('income_statement');
});

it('treats only leaf accounts as postable', function () {
    $parent = Account::factory()->create();
    $child = Account::factory()->childOf($parent)->create();

    expect($parent->fresh()->is_postable)->toBeFalse()
        ->and($child->fresh()->is_postable)->toBeTrue();
});

it('attaches every ancestor when an account joins a chart', function () {
    $root = Account::factory()->create();
    $middle = Account::factory()->childOf($root)->create();
    $leaf = Account::factory()->childOf($middle)->create();

    $chart = Chart::factory()->create(['levels_count' => 5]);
    $chart->attachAccount($leaf);

    expect($chart->accounts()->pluck('accounts.id')->sort()->values()->all())
        ->toBe(collect([$root->id, $middle->id, $leaf->id])->sort()->values()->all());
});

it('refuses an account deeper than the chart allows', function () {
    $root = Account::factory()->create();
    $middle = Account::factory()->childOf($root)->create();
    $leaf = Account::factory()->childOf($middle)->create();

    $chart = Chart::factory()->create(['levels_count' => 2]);

    expect(fn () => $chart->attachAccount($leaf))
        ->toThrow(RuntimeException::class);

    assertDatabaseCount('chart_account', 0);
});

it('detaches descendants along with the account', function () {
    $root = Account::factory()->create();
    $middle = Account::factory()->childOf($root)->create();
    $leaf = Account::factory()->childOf($middle)->create();

    $chart = Chart::factory()->create(['levels_count' => 5]);
    $chart->attachAccount($leaf);

    $chart->detachAccount($middle);

    expect($chart->accounts()->pluck('accounts.id')->all())->toBe([$root->id]);
});

it('builds hierarchical codes for every general ledger record', function () {
    $category = AccountCategory::factory()->create(['name' => 'Current Assets']);
    $account = Account::factory()->create(['name' => 'Petty Cash', 'category_id' => $category->id]);
    $chart = Chart::factory()->create(['name' => 'Statutory Chart']);

    expect($category->code)->toStartWith('fin-gl-cat-')
        ->and($account->code)->toStartWith('fin-gl-acc-')
        ->and($chart->code)->toStartWith('fin-gl-coa-');
});
