<?php

use App\Support\DynamicTable\Core\Columns\RecordReferenceColumn;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Exceptions\InvalidModelException;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TableState;
use App\Support\DynamicTable\Query\TableQueryBuilder;
use App\Support\RecordReference\RecordReferenceVariant;
use Illuminate\Support\Facades\DB;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;

beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();

    foreach (range(1, 10) as $i) {
        Country::create(['name' => "Country {$i}", 'iso2' => 'C'.$i, 'iso3' => 'C'.$i.'X', 'region' => 'Region', 'subregion' => 'Sub', 'phone_code' => (string) $i, 'status' => 1]);
    }
});

function countriesDefinition(RecordReferenceVariant $variant): TableDefinition
{
    return new TableDefinition(
        tableKey: 'countries',
        columns: [
            RecordReferenceColumn::make('reference')->applicationCode('gen-wld-ctr')->variant($variant),
            TextColumn::make('iso2'),
        ],
        filters: [],
        query: fn () => Country::query(),
    );
}

it('selects only identity columns for the icon/tag variant, not preview-only columns', function (): void {
    $definition = countriesDefinition(RecordReferenceVariant::Icon);
    $builder = new TableQueryBuilder($definition);
    $state = TableState::normalize([], $definition);

    $sql = $builder->query($state)->toSql();

    expect($sql)->toContain('"name"')
        ->not->toContain('"native"');
});

it('includes declared card columns only when the card variant is visible', function (): void {
    $definition = countriesDefinition(RecordReferenceVariant::Card);
    $builder = new TableQueryBuilder($definition);
    $state = TableState::normalize([], $definition);

    $sql = $builder->query($state)->toSql();

    expect($sql)->toContain('"region"');
});

it('keeps a constant query count for the initial page regardless of row count (no per-row preview query)', function (): void {
    $definition = countriesDefinition(RecordReferenceVariant::Tag);
    $builder = new TableQueryBuilder($definition);
    $state = TableState::normalize(['perPage' => 10], $definition);

    DB::enableQueryLog();
    $builder->paginate($state);
    $smallCount = count(DB::getQueryLog());
    DB::flushQueryLog();

    DB::disableQueryLog();
    foreach (range(11, 60) as $i) {
        Country::create(['name' => "Country {$i}", 'iso2' => 'C'.$i, 'iso3' => 'C'.$i.'X', 'phone_code' => (string) $i, 'region' => 'Region', 'subregion' => 'Sub', 'status' => 1]);
    }
    DB::flushQueryLog();
    DB::enableQueryLog();

    $state = TableState::normalize(['perPage' => 50], $definition);
    $builder->paginate($state);
    $largeCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($largeCount)->toBe($smallCount);
});

it('related belongsTo selects only owner key and identity columns', function (): void {
    $definition = new TableDefinition(
        tableKey: 'cities',
        columns: [
            RecordReferenceColumn::make('country')->relation('country')->applicationCode('gen-wld-ctr')->variant(RecordReferenceVariant::Tag),
        ],
        filters: [],
        query: fn () => City::query(),
    );

    $builder = new TableQueryBuilder($definition);
    $state = TableState::normalize([], $definition);
    $query = $builder->query($state);

    $eagerLoads = $query->getEagerLoads();
    expect($eagerLoads)->toHaveKey('country');

    $mockQuery = Country::query();
    $eagerLoads['country']($mockQuery);
    $sql = $mockQuery->toSql();

    expect($sql)->toContain('"id"')
        ->toContain('"name"')
        ->toContain('"status"')
        ->toContain('"status" = ?')
        ->not->toContain('"region"');
});

it('related Card variant adds card columns but not preview-only columns', function (): void {
    $definition = new TableDefinition(
        tableKey: 'cities',
        columns: [
            RecordReferenceColumn::make('country')->relation('country')->applicationCode('gen-wld-ctr')->variant(RecordReferenceVariant::Card),
        ],
        filters: [],
        query: fn () => City::query(),
    );

    $builder = new TableQueryBuilder($definition);
    $state = TableState::normalize([], $definition);
    $query = $builder->query($state);

    $eagerLoads = $query->getEagerLoads();
    $mockQuery = Country::query();
    $eagerLoads['country']($mockQuery);
    $sql = $mockQuery->toSql();

    expect($sql)->toContain('"id"')
        ->toContain('"name"')
        ->toContain('"region"')
        ->not->toContain('"iso3"');
});

it('throws InvalidModelException for mismatched provider and query model in self reference', function (): void {
    $definition = new TableDefinition(
        tableKey: 'cities-mismatch',
        columns: [
            RecordReferenceColumn::make('self')->applicationCode('gen-wld-ctr'),
        ],
        filters: [],
        query: fn () => City::query(),
    );

    $builder = new TableQueryBuilder($definition);
    $state = TableState::normalize([], $definition);

    $builder->query($state);
})->throws(InvalidModelException::class);

it('throws InvalidModelException for mismatched provider and related model', function (): void {
    $definition = new TableDefinition(
        tableKey: 'cities-relation-mismatch',
        columns: [
            RecordReferenceColumn::make('state')->relation('state')->applicationCode('gen-wld-ctr'),
        ],
        filters: [],
        query: fn () => City::query(),
    );

    $builder = new TableQueryBuilder($definition);
    $state = TableState::normalize([], $definition);

    $builder->query($state);
})->throws(InvalidModelException::class);

it('throws InvalidArgumentException for dotted relation paths', function (): void {
    RecordReferenceColumn::make('country.state')->relation('country.state')->applicationCode('gen-wld-ctr')->validate();
})->throws(InvalidArgumentException::class, 'only supports first-level belongsTo relation paths');
