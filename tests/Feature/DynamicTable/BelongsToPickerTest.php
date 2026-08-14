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
    Schema::create('picker_test_authors', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('country');
        $table->string('internal_salary_note')->nullable(); // must never be selected by the picker
    });

    Schema::create('picker_test_posts', function ($table) {
        $table->id();
        $table->foreignId('author_id')->nullable();
        $table->string('title');
    });
});

class PickerTestAuthor extends Model
{
    protected $table = 'picker_test_authors';

    public $timestamps = false;

    protected $fillable = ['name', 'country', 'internal_salary_note'];
}

class PickerTestPost extends Model
{
    protected $table = 'picker_test_posts';

    public $timestamps = false;

    protected $fillable = ['author_id', 'title'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(PickerTestAuthor::class, 'author_id');
    }
}

function pickerTableClass(bool $multiple = false): string
{
    return $multiple ? PickerMultipleTable::class : PickerSingleTable::class;
}

class PickerSingleTable extends Table
{
    protected string $tableKey = 'belongsto-picker-test';

    protected ?string $model = PickerTestPost::class;

    protected function columns(): array
    {
        return [TextColumn::make('title')];
    }

    protected function filters(): array
    {
        return [
            BelongsToFilter::make('author')
                ->displayUsing(fn ($author) => $author->name)
                ->searchUsing(['name', 'country']),
        ];
    }
}

class PickerMultipleTable extends Table
{
    protected string $tableKey = 'belongsto-picker-test-multi';

    protected ?string $model = PickerTestPost::class;

    protected function columns(): array
    {
        return [TextColumn::make('title')];
    }

    protected function filters(): array
    {
        return [
            BelongsToFilter::make('author')
                ->displayUsing(fn ($author) => $author->name)
                ->searchUsing(['name', 'country'])
                ->multiple(),
        ];
    }
}

test('searching below the minimum length returns no options and issues no query', function () {
    PickerTestAuthor::create(['name' => 'Ada Lovelace', 'country' => 'UK']);

    $component = Livewire::test(pickerTableClass());

    DB::enableQueryLog();
    $component->call('searchBelongsTo', 'author', 'a'); // 1 char, below BELONGS_TO_MIN_SEARCH_LENGTH
    $authorQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'picker_test_authors'));
    DB::disableQueryLog();

    expect($component->get('belongsToOptions')['author'] ?? [])->toBe([])
        ->and($authorQueries)->toHaveCount(0);
});

test('searching at or above the minimum length returns matching options', function () {
    PickerTestAuthor::create(['name' => 'Ada Lovelace', 'country' => 'UK']);
    PickerTestAuthor::create(['name' => 'Grace Hopper', 'country' => 'US']);

    $component = Livewire::test(pickerTableClass())
        ->call('searchBelongsTo', 'author', 'Ada');

    $options = $component->get('belongsToOptions')['author'];

    expect($options)->toHaveCount(1)
        ->and($options[0]['label'])->toBe('Ada Lovelace');
});

test('the search matches every declared search field not just the primary display field', function () {
    PickerTestAuthor::create(['name' => 'Ada Lovelace', 'country' => 'UK']);
    PickerTestAuthor::create(['name' => 'Grace Hopper', 'country' => 'US']);

    // 'UK' only matches the country field, not the name field.
    $component = Livewire::test(pickerTableClass())
        ->call('searchBelongsTo', 'author', 'UK');

    $options = $component->get('belongsToOptions')['author'];

    expect($options)->toHaveCount(1)
        ->and($options[0]['label'])->toBe('Ada Lovelace');
});

test('results are capped at the maximum result count and never load the entire table', function () {
    for ($i = 0; $i < Table::BELONGS_TO_MAX_RESULTS + 15; $i++) {
        PickerTestAuthor::create(['name' => "Match Author {$i}", 'country' => 'UK']);
    }

    $component = Livewire::test(pickerTableClass())
        ->call('searchBelongsTo', 'author', 'Match');

    expect($component->get('belongsToOptions')['author'])->toHaveCount(Table::BELONGS_TO_MAX_RESULTS);
});

test('the picker query selects only the primary key and declared display/search fields', function () {
    PickerTestAuthor::create(['name' => 'Ada Lovelace', 'country' => 'UK', 'internal_salary_note' => 'TOP-SECRET-SALARY']);

    DB::enableQueryLog();
    Livewire::test(pickerTableClass())->call('searchBelongsTo', 'author', 'Ada');
    $query = collect(DB::getQueryLog())->last()['query'];
    DB::disableQueryLog();

    expect($query)->not->toContain('internal_salary_note');
});

test('a label containing html is escaped when rendered, not executed', function () {
    PickerTestAuthor::create(['name' => '<script>alert(1)</script>', 'country' => 'UK']);

    $html = Livewire::test(pickerTableClass())
        ->call('searchBelongsTo', 'author', 'script')
        ->html();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('&lt;script&gt;');
});

test('a search targeting an unauthorized or non existent filter key is a silent no-op', function () {
    $component = Livewire::test(pickerTableClass());

    $component->call('searchBelongsTo', 'not_a_real_filter', 'anything');

    expect($component->get('belongsToOptions'))->toBe([]);
});

test('selecting a single option sets the filter value and clears the search state', function () {
    $author = PickerTestAuthor::create(['name' => 'Ada Lovelace', 'country' => 'UK']);

    $component = Livewire::test(pickerTableClass())
        ->call('searchBelongsTo', 'author', 'Ada')
        ->call('selectBelongsToOption', 'author', $author->id);

    expect($component->get('filters')['author'])->toBe(['operator' => 'equals', 'value' => $author->id])
        ->and($component->get('belongsToOptions')['author'] ?? [])->toBe([]);
});

test('selecting multiple options accumulates ids without duplicates', function () {
    $a1 = PickerTestAuthor::create(['name' => 'Ada Lovelace', 'country' => 'UK']);
    $a2 = PickerTestAuthor::create(['name' => 'Grace Hopper', 'country' => 'US']);

    $component = Livewire::test(pickerTableClass(multiple: true))
        ->call('selectBelongsToOption', 'author', $a1->id)
        ->call('selectBelongsToOption', 'author', $a2->id)
        ->call('selectBelongsToOption', 'author', $a1->id); // duplicate, ignored

    expect($component->get('filters')['author'])->toBe(['operator' => 'in', 'value' => [$a1->id, $a2->id]]);
});

test('removing a multiple selection drops only that id', function () {
    $a1 = PickerTestAuthor::create(['name' => 'Ada Lovelace', 'country' => 'UK']);
    $a2 = PickerTestAuthor::create(['name' => 'Grace Hopper', 'country' => 'US']);

    $component = Livewire::test(pickerTableClass(multiple: true))
        ->call('selectBelongsToOption', 'author', $a1->id)
        ->call('selectBelongsToOption', 'author', $a2->id)
        ->call('removeBelongsToOption', 'author', $a1->id);

    expect($component->get('filters')['author'])->toBe(['operator' => 'in', 'value' => [$a2->id]]);
});

test('a previously selected id from a restored state resolves its label without a manual search', function () {
    $author = PickerTestAuthor::create(['name' => 'Ada Lovelace', 'country' => 'UK']);
    PickerTestPost::create(['author_id' => $author->id, 'title' => 'Post']);

    $html = Livewire::test(pickerTableClass())
        ->set('filters.author', ['operator' => 'equals', 'value' => $author->id])
        ->set('appliedFilters', ['author' => ['operator' => 'equals', 'value' => $author->id]])
        ->html();

    expect($html)->toContain('Ada Lovelace');
});

test('the picker never issues a query on initial mount before any interaction', function () {
    PickerTestAuthor::create(['name' => 'Ada Lovelace', 'country' => 'UK']);

    DB::enableQueryLog();
    Livewire::test(pickerTableClass());
    $authorQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'picker_test_authors'));
    DB::disableQueryLog();

    expect($authorQueries)->toHaveCount(0);
});
