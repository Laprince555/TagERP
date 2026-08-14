<?php

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\BelongsToFilter;
use App\Support\DynamicTable\Core\TableState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    Schema::create('picker_sec_authors', function ($table) {
        $table->id();
        $table->string('name');
        $table->boolean('active')->default(true);
        $table->unsignedInteger('tenant_id')->default(1);
    });

    Schema::create('picker_sec_posts', function ($table) {
        $table->id();
        $table->unsignedInteger('author_id')->nullable();
        $table->string('title');
    });

    Schema::create('picker_sec_uuid_categories', function ($table) {
        $table->uuid('id')->primary();
        $table->string('name');
    });

    Schema::create('picker_sec_uuid_posts', function ($table) {
        $table->id();
        $table->uuid('category_id')->nullable();
        $table->string('title');
    });
});

class PickerSecAuthor extends Model
{
    protected $table = 'picker_sec_authors';

    public $timestamps = false;

    protected $fillable = ['name', 'active', 'tenant_id'];
}

class PickerSecPost extends Model
{
    protected $table = 'picker_sec_posts';

    public $timestamps = false;

    protected $fillable = ['author_id', 'title'];

    // Relation-local constraint: only "active" authors are ever a valid option —
    // this must survive into the picker's option query, not just global scopes.
    public function author(): BelongsTo
    {
        return $this->belongsTo(PickerSecAuthor::class, 'author_id')->where('active', true);
    }
}

class PickerSecUuidCategory extends Model
{
    protected $table = 'picker_sec_uuid_categories';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name'];
}

class PickerSecUuidPost extends Model
{
    protected $table = 'picker_sec_uuid_posts';

    public $timestamps = false;

    protected $fillable = ['category_id', 'title'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PickerSecUuidCategory::class, 'category_id');
    }
}

class PickerSecRelationConstraintTable extends Table
{
    protected string $tableKey = 'picker-sec-relation-constraint';

    protected ?string $model = PickerSecPost::class;

    protected function columns(): array
    {
        return [TextColumn::make('title')];
    }

    protected function filters(): array
    {
        return [
            BelongsToFilter::make('author')->displayUsing(fn ($a) => $a->name)->searchUsing(['name']),
        ];
    }
}

class PickerSecTenantScopedTable extends Table
{
    protected string $tableKey = 'picker-sec-tenant-scoped';

    protected ?string $model = PickerSecPost::class;

    protected function columns(): array
    {
        return [TextColumn::make('title')];
    }

    protected function filters(): array
    {
        return [
            BelongsToFilter::make('author')
                ->displayUsing(fn ($a) => $a->name)
                ->searchUsing(['name'])
                ->optionsQuery(fn ($query) => $query->where('tenant_id', 2)),
        ];
    }
}

class PickerSecAuthorizeOptionTable extends Table
{
    protected string $tableKey = 'picker-sec-authorize-option';

    protected ?string $model = PickerSecPost::class;

    protected function columns(): array
    {
        return [TextColumn::make('title')];
    }

    protected function filters(): array
    {
        return [
            BelongsToFilter::make('author')
                ->displayUsing(fn ($a) => $a->name)
                ->searchUsing(['name'])
                ->authorizeOption(fn ($a) => $a->name !== 'Blocked Author'),
        ];
    }
}

class PickerSecUuidTable extends Table
{
    protected string $tableKey = 'picker-sec-uuid';

    protected ?string $model = PickerSecUuidPost::class;

    protected function columns(): array
    {
        return [TextColumn::make('title')];
    }

    protected function filters(): array
    {
        return [
            BelongsToFilter::make('category')->displayUsing(fn ($c) => $c->name)->searchUsing(['name']),
        ];
    }
}

test('a relation local where constraint is preserved in the option query', function () {
    PickerSecAuthor::create(['name' => 'Active Author', 'active' => true]);
    PickerSecAuthor::create(['name' => 'Inactive Author', 'active' => false]);

    $options = Livewire::test(PickerSecRelationConstraintTable::class)
        ->call('searchBelongsTo', 'author', 'Author')
        ->get('belongsToOptions')['author'];

    expect(collect($options)->pluck('label')->all())->toBe(['Active Author']);
});

test('a relation local where constraint blocks selecting a forged id for an excluded row', function () {
    $inactive = PickerSecAuthor::create(['name' => 'Inactive Author', 'active' => false]);

    $component = Livewire::test(PickerSecRelationConstraintTable::class)
        ->call('selectBelongsToOption', 'author', $inactive->id);

    expect($component->get('filters'))->not->toHaveKey('author');
});

test('a tenant scoping optionsQuery hook is enforced', function () {
    PickerSecAuthor::create(['name' => 'Tenant One Author', 'tenant_id' => 1]);
    PickerSecAuthor::create(['name' => 'Tenant Two Author', 'tenant_id' => 2]);

    $options = Livewire::test(PickerSecTenantScopedTable::class)
        ->call('searchBelongsTo', 'author', 'Author')
        ->get('belongsToOptions')['author'];

    expect(collect($options)->pluck('label')->all())->toBe(['Tenant Two Author']);
});

test('a tenant scoping optionsQuery hook blocks selecting a forged out of tenant id', function () {
    $outOfTenant = PickerSecAuthor::create(['name' => 'Tenant One Author', 'tenant_id' => 1]);

    $component = Livewire::test(PickerSecTenantScopedTable::class)
        ->call('selectBelongsToOption', 'author', $outOfTenant->id);

    expect($component->get('filters'))->not->toHaveKey('author');
});

test('authorizeOption drops an option that fails a php level authorization check', function () {
    PickerSecAuthor::create(['name' => 'Blocked Author', 'active' => true]);
    PickerSecAuthor::create(['name' => 'Allowed Author', 'active' => true]);

    $options = Livewire::test(PickerSecAuthorizeOptionTable::class)
        ->call('searchBelongsTo', 'author', 'Author')
        ->get('belongsToOptions')['author'];

    expect(collect($options)->pluck('label')->all())->toBe(['Allowed Author']);
});

test('authorizeOption blocks selecting a forged id for an unauthorized option', function () {
    $blocked = PickerSecAuthor::create(['name' => 'Blocked Author', 'active' => true]);

    $component = Livewire::test(PickerSecAuthorizeOptionTable::class)
        ->call('selectBelongsToOption', 'author', $blocked->id);

    expect($component->get('filters'))->not->toHaveKey('author');
});

test('a forged non existent id is never accepted', function () {
    $component = Livewire::test(PickerSecRelationConstraintTable::class)
        ->call('selectBelongsToOption', 'author', 999999);

    expect($component->get('filters'))->not->toHaveKey('author');
});

test('uuid primary keys work end to end for search select and apply', function () {
    $uuid = (string) Str::uuid();
    PickerSecUuidCategory::create(['id' => $uuid, 'name' => 'Uuid Category']);

    $component = Livewire::test(PickerSecUuidTable::class)
        ->call('searchBelongsTo', 'category', 'Uuid');

    $options = $component->get('belongsToOptions')['category'];
    expect($options[0]['id'])->toBe($uuid);

    $component->call('selectBelongsToOption', 'category', $uuid);

    expect($component->get('filters')['category'])->toBe(['operator' => 'equals', 'value' => $uuid]);
});

test('the option query never issues an unbounded select and stays capped', function () {
    for ($i = 0; $i < 30; $i++) {
        PickerSecAuthor::create(['name' => "Bulk Author {$i}"]);
    }

    $options = Livewire::test(PickerSecRelationConstraintTable::class)
        ->call('searchBelongsTo', 'author', 'Bulk')
        ->get('belongsToOptions')['author'];

    expect($options)->toHaveCount(Table::BELONGS_TO_MAX_RESULTS);
});

test('search input longer than the max search length is truncated not rejected outright', function () {
    PickerSecAuthor::create(['name' => str_repeat('a', 250)]);

    $component = Livewire::test(PickerSecRelationConstraintTable::class)
        ->call('searchBelongsTo', 'author', str_repeat('a', 500));

    expect(mb_strlen($component->get('belongsToSearch')['author']))->toBeLessThanOrEqual(TableState::MAX_SEARCH_LENGTH);
});
