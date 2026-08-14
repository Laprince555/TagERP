<?php

use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Exceptions\ModelNotSearchableException;
use App\Support\DynamicTable\Core\TableDefinition;
use App\Support\DynamicTable\Core\TableState;
use App\Support\DynamicTable\Query\SearchDrivers\DatabaseSearchDriver;
use App\Support\DynamicTable\Query\SearchDrivers\ScoutSearchDriver;
use App\Support\DynamicTable\Query\TableQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\Searchable;

beforeEach(function () {
    Schema::create('search_driver_test_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->boolean('active')->default(true);
    });

    Schema::create('searchable_test_models', function ($table) {
        $table->id();
        $table->string('name');
        $table->boolean('active')->default(true);
        $table->string('secret_note')->nullable();
    });
});

class SearchDriverTestModel extends Model
{
    protected $table = 'search_driver_test_models';

    public $timestamps = false;

    protected $fillable = ['name', 'active'];
}

class ScoutSearchableTestModel extends Model
{
    use Searchable;

    protected $table = 'searchable_test_models';

    public $timestamps = false;

    protected $fillable = ['name', 'active', 'secret_note'];

    public function toSearchableArray(): array
    {
        return ['name' => $this->name, 'secret_note' => $this->secret_note];
    }
}

function searchDriverDefinition(): TableDefinition
{
    return new TableDefinition(
        tableKey: 'search-driver-test',
        columns: [TextColumn::make('name')->searchable()],
        filters: [],
        query: fn (): Builder => SearchDriverTestModel::query()->where('active', true),
    );
}

test('the database search driver is the default and preserves base query scopes', function () {
    SearchDriverTestModel::create(['name' => 'Findable Active', 'active' => true]);
    SearchDriverTestModel::create(['name' => 'Findable Inactive', 'active' => false]);

    $definition = searchDriverDefinition();
    $state = TableState::normalize(['search' => 'Findable'], $definition);
    $rows = (new TableQueryBuilder($definition))->paginate($state);

    expect($rows->total())->toBe(1)
        ->and($rows->first()->name)->toBe('Findable Active');
});

test('an explicit database search driver instance behaves identically to the default', function () {
    SearchDriverTestModel::create(['name' => 'Explicit Driver Match', 'active' => true]);

    $definition = searchDriverDefinition();
    $state = TableState::normalize(['search' => 'Explicit Driver'], $definition);
    $rows = (new TableQueryBuilder($definition, new DatabaseSearchDriver))->paginate($state);

    expect($rows->total())->toBe(1);
});

test('the scout search driver rejects a model that does not use the Searchable trait', function () {
    $definition = searchDriverDefinition();
    $state = TableState::normalize(['search' => 'x'], $definition);

    expect(fn () => (new TableQueryBuilder($definition, new ScoutSearchDriver))->paginate($state))
        ->toThrow(ModelNotSearchableException::class);
});

test('the scout search driver constrains the base scoped query to matching ids and never bypasses it', function () {
    // Collection driver indexes/searches in-memory over the actual DB rows — no external service needed.
    Config::set('scout.driver', 'collection');

    $active = ScoutSearchableTestModel::create(['name' => 'Scout Findable Active', 'active' => true]);
    $inactive = ScoutSearchableTestModel::create(['name' => 'Scout Findable Inactive', 'active' => false]);

    $definition = new TableDefinition(
        tableKey: 'scout-driver-test',
        columns: [TextColumn::make('name')->searchable()],
        filters: [],
        // Base scope (active = true) must survive being combined with Scout's whereIn() constraint.
        query: fn (): Builder => ScoutSearchableTestModel::query()->where('active', true),
    );

    $state = TableState::normalize(['search' => 'Scout Findable'], $definition);
    $rows = (new TableQueryBuilder($definition, new ScoutSearchDriver))->paginate($state);

    expect($rows->total())->toBe(1)
        ->and($rows->first()->id)->toBe($active->id)
        ->and($rows->pluck('id'))->not->toContain($inactive->id);
});

test('the scout search driver never returns a row that only matches on a non searchable, unauthorized field', function () {
    Config::set('scout.driver', 'collection');

    // Indexed by Scout (present in toSearchableArray) but NOT declared
    // ->searchable() on the table's column — must never be matchable.
    ScoutSearchableTestModel::create(['name' => 'Ordinary Row', 'active' => true, 'secret_note' => 'TopSecretPhrase']);
    ScoutSearchableTestModel::create(['name' => 'TopSecretPhrase', 'active' => true]);

    $definition = new TableDefinition(
        tableKey: 'scout-driver-unauthorized-field-test',
        columns: [TextColumn::make('name')->searchable()],
        filters: [],
        query: fn (): Builder => ScoutSearchableTestModel::query()->where('active', true),
    );

    $state = TableState::normalize(['search' => 'TopSecretPhrase'], $definition);
    $rows = (new TableQueryBuilder($definition, new ScoutSearchDriver))->paginate($state);

    expect($rows->total())->toBe(1)
        ->and($rows->first()->name)->toBe('TopSecretPhrase');
});

test('the scout search driver bounds the number of ids it loads from the index', function () {
    Config::set('scout.driver', 'collection');

    foreach (range(1, ScoutSearchDriver::MAX_MATCHED_IDS + 10) as $i) {
        ScoutSearchableTestModel::create(['name' => "Bulk Match {$i}", 'active' => true]);
    }

    $definition = new TableDefinition(
        tableKey: 'scout-driver-bounded-ids-test',
        columns: [TextColumn::make('name')->searchable()],
        filters: [],
        query: fn (): Builder => ScoutSearchableTestModel::query()->where('active', true),
    );

    $driver = new ScoutSearchDriver;
    $state = TableState::normalize(['search' => 'Bulk Match'], $definition);
    $query = ($definition->query)();
    $result = $driver->search($query, $state->search, $definition->authorizedColumns());

    $idsBinding = collect($result->getQuery()->wheres)
        ->firstWhere('column', $query->getModel()->getQualifiedKeyName());

    expect($idsBinding['values'])->toHaveCount(ScoutSearchDriver::MAX_MATCHED_IDS);
});
