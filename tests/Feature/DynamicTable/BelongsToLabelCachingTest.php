<?php

use App\Livewire\DynamicTable\Table;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\DynamicTable\Core\Filters\BelongsToFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    Schema::create('label_cache_authors', function ($table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('label_cache_posts', function ($table) {
        $table->id();
        $table->unsignedInteger('author_id')->nullable();
        $table->string('title');
    });
});

class LabelCacheAuthor extends Model
{
    protected $table = 'label_cache_authors';

    public $timestamps = false;

    protected $fillable = ['name'];
}

class LabelCachePost extends Model
{
    protected $table = 'label_cache_posts';

    public $timestamps = false;

    protected $fillable = ['author_id', 'title'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(LabelCacheAuthor::class, 'author_id');
    }
}

class LabelCacheTestTable extends Table
{
    protected string $tableKey = 'label-cache-test';

    protected ?string $model = LabelCachePost::class;

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

test('one applied belongsTo filter resolves labels exactly once per render, not once per ui consumer', function () {
    $author = LabelCacheAuthor::create(['name' => 'Shared Label Author']);

    $component = Livewire::test(LabelCacheTestTable::class)
        ->set('filters.author', ['operator' => 'equals', 'value' => $author->id])
        ->call('applyFilters');

    DB::enableQueryLog();
    $component->call('submitSearch'); // forces a fresh render()
    $labelQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_starts_with($q['query'], 'select "id", "name" from "label_cache_authors"'));
    DB::disableQueryLog();

    expect($labelQueries)->toHaveCount(1);
});

test('label query count does not grow with the number of table rows', function () {
    $author = LabelCacheAuthor::create(['name' => 'Bounded Author']);
    collect(range(1, 50))->each(fn ($i) => LabelCachePost::create(['author_id' => $author->id, 'title' => "Post {$i}"]));

    $component = Livewire::test(LabelCacheTestTable::class)
        ->set('filters.author', ['operator' => 'equals', 'value' => $author->id])
        ->call('applyFilters');

    DB::enableQueryLog();
    $component->call('submitSearch');
    $labelQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_starts_with($q['query'], 'select "id", "name" from "label_cache_authors"'));
    DB::disableQueryLog();

    expect($labelQueries)->toHaveCount(1);
});

test('clearing a filter then reselecting refreshes the resolved label correctly', function () {
    $author = LabelCacheAuthor::create(['name' => 'Reselect Author']);

    $component = Livewire::test(LabelCacheTestTable::class)
        ->set('filters.author', ['operator' => 'equals', 'value' => $author->id])
        ->call('applyFilters')
        ->assertSee('Reselect Author');

    $component->call('clearFilter', 'author')
        ->assertDontSee('Reselect Author');

    $component->set('filters.author', ['operator' => 'equals', 'value' => $author->id])
        ->call('applyFilters')
        ->assertSee('Reselect Author');
});
