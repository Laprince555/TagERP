<?php

use App\Jobs\ImportTableJob;
use App\Livewire\DynamicTable\Table;
use App\Livewire\Imports\ImportPage;
use App\Livewire\Imports\ImportRowsTable;
use App\Models\Import;
use App\Models\User;
use App\Support\DynamicForm\Core\CascadingLevel;
use App\Support\DynamicForm\Core\DynamicForm;
use App\Support\DynamicForm\Core\Fields\CascadingRelationField;
use App\Support\DynamicForm\Core\Fields\TextField;
use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use App\Support\DynamicTable\Core\Columns\TextColumn;
use App\Support\Import\FormRowImporter;
use App\Support\Import\ImportRowStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\General\Models\World\City;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\Country;
use Modules\General\Models\World\State;
use Modules\General\System\Application;

class ImportTestCompanyForm extends DynamicForm
{
    public function model(): string
    {
        return Company::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            TextField::make('tax_id')->label('Tax ID'),
            TextField::make('email')->type('email')->label('Email')->rules(['email']),
            CascadingRelationField::make('city')
                ->level(CascadingLevel::make('country', Country::class)->field('name'))
                ->level(CascadingLevel::make('state', State::class)->field('name')->dependsOn('country'))
                ->level(CascadingLevel::make('city', City::class)->field('name')->dependsOn('state')),
        ];
    }
}

class ImportTestTable extends Table
{
    protected string $tableKey = 'import-test';

    protected ?string $model = Company::class;

    protected function columns(): array
    {
        return [TextColumn::make('name')];
    }

    protected function filters(): array
    {
        return [];
    }

    protected function createForm(): ?string
    {
        return 'import-test.company.create';
    }
}

/** Import is on by default now, so the interesting case is a table opting out. */
class NotImportableTestTable extends ImportTestTable
{
    protected string $tableKey = 'import-test-closed';

    public function canImport(): bool
    {
        return false;
    }
}

/** No create form means nothing to import into â€” the button must stay off. */
class NoCreateFormTestTable extends ImportTestTable
{
    protected string $tableKey = 'import-test-formless';

    protected function createForm(): ?string
    {
        return null;
    }
}

beforeEach(function () {
    app(FormDefinitionRegistry::class)->has('import-test.company.create')
        || app(FormDefinitionRegistry::class)->register('import-test.company.create', ImportTestCompanyForm::class);

    // The importer asks the form's create permission, which is namespaced by
    // the Company Application — without the row there is no namespace and the
    // import correctly refuses.
    Application::factory()->create(['code' => Company::APPLICATION_CODE, 'is_active' => true, 'permission_name' => null]);
});

/** Writes a CSV to the local fake disk and returns the Import pointing at it. */
function stageImport(User $user, string $csv, string $filename = 'companies.csv'): Import
{
    $path = 'imports/'.$user->id.'/'.uniqid().'.csv';
    Storage::disk('local')->put($path, $csv);

    return Import::create([
        'user_id' => $user->id,
        'table_class' => ImportTestTable::class,
        'form_key' => 'import-test.company.create',
        'filename' => $filename,
        'path' => $path,
        'status' => Import::STATUS_QUEUED,
    ]);
}

test('the template columns come from the import form, not a committed fixture', function () {
    $actor = superAdmin();

    $columns = Livewire::actingAs($actor)
        ->test(ImportTestTable::class)
        ->instance()
        ->importTemplateColumns();

    expect(array_column($columns, 'key'))->toBe(['name', 'tax_id', 'email', 'city'])
        ->and($columns[0]['required'])->toBeTrue()
        ->and($columns[1]['required'])->toBeFalse();

    expect((new FormRowImporter(new ImportTestCompanyForm))->headers())
        ->toBe(['name', 'tax_id', 'email', 'city']);
});

test('uploading a file queues a job and notifies the uploader', function () {
    Queue::fake();
    Storage::fake('local');

    $actor = superAdmin();

    Livewire::actingAs($actor)
        ->test(ImportTestTable::class)
        ->set('importFile', UploadedFile::fake()->createWithContent('companies.csv', "name\nAcme\n"))
        ->call('startImport')
        ->assertHasNoErrors();

    $import = Import::sole();

    expect($import->user_id)->toBe($actor->id)
        ->and($import->filename)->toBe('companies.csv')
        ->and($actor->fresh()->notifications()->sole()->data['type'])->toBe('import');

    Queue::assertPushed(ImportTableJob::class, fn (ImportTableJob $job) => $job->importId === $import->id);
});

test('every table with a create form can import, and one without cannot', function () {
    $actor = superAdmin();

    $importable = Livewire::actingAs($actor)->test(ImportTestTable::class)->instance();
    $formless = Livewire::actingAs($actor)->test(NoCreateFormTestTable::class)->instance();

    expect($importable->canImport())->toBeTrue()
        ->and($formless->canImport())->toBeFalse()
        ->and($formless->importTemplateColumns())->toBe([]);
});

test('a table that has opted out refuses to import', function () {
    Storage::fake('local');

    $actor = superAdmin();

    Livewire::actingAs($actor)
        ->test(NotImportableTestTable::class)
        ->set('importFile', UploadedFile::fake()->createWithContent('companies.csv', "name\nAcme\n"))
        ->call('startImport')
        ->assertStatus(403);

    expect(Import::count())->toBe(0);
});

test('a non-spreadsheet upload is rejected before anything is queued', function () {
    Queue::fake();
    Storage::fake('local');

    $actor = superAdmin();

    Livewire::actingAs($actor)
        ->test(ImportTestTable::class)
        ->set('importFile', UploadedFile::fake()->create('payload.php', 10))
        ->call('startImport')
        ->assertHasErrors('importFile');

    Queue::assertNothingPushed();
    expect(Import::count())->toBe(0);
});

test('the queued import creates the good rows and records why the bad ones failed', function () {
    Storage::fake('local');

    $actor = superAdmin();
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);
    $state = State::create(['name' => 'Giza', 'country_id' => $country->id, 'country_code' => 'EG']);
    City::create(['name' => 'Faisal', 'country_id' => $country->id, 'state_id' => $state->id, 'country_code' => 'EG']);

    $import = stageImport($actor, <<<'CSV'
    name,tax_id,email,city
    Acme,100,acme@example.com,Faisal
    ,200,noname@example.com,Faisal
    Globex,300,not-an-email,Faisal
    Initech,400,initech@example.com,Atlantis
    CSV);

    (new ImportTableJob($import->id))->handle();

    $import->refresh();
    $rows = $import->rows()->orderBy('row_number')->get();

    expect($import->status)->toBe(Import::STATUS_COMPLETED)
        ->and($import->total_rows)->toBe(4)
        ->and($import->imported_rows)->toBe(1)
        ->and($import->failed_rows)->toBe(3)
        ->and($rows[0]->status)->toBe(ImportRowStatus::Imported)
        ->and($rows[1]->status)->toBe(ImportRowStatus::Failed)
        ->and($rows[2]->error)->toContain('email')
        ->and($rows[3]->error)->toContain('Atlantis');

    $company = Company::sole();

    expect($company->name)->toBe('Acme')
        ->and($company->city->name)->toBe('Faisal')
        ->and($actor->fresh()->notifications()->latest()->first()->data['title'])->toBe('Import finished');
});

test('a file whose header row matches nothing fails as a whole instead of row by row', function () {
    Storage::fake('local');

    $actor = superAdmin();
    $import = stageImport($actor, "colour,size\nred,large\n");

    $job = new ImportTableJob($import->id);

    try {
        $job->handle();
    } catch (Throwable $exception) {
        $job->failed($exception);
    }

    $import->refresh();

    expect($import->status)->toBe(Import::STATUS_FAILED)
        ->and($import->error)->toContain('header row')
        ->and(Company::count())->toBe(0);
});

test('re-running a job resumes instead of importing the file twice', function () {
    Storage::fake('local');

    $actor = superAdmin();
    $import = stageImport($actor, "name\nAcme\n");

    (new ImportTableJob($import->id))->handle();
    (new ImportTableJob($import->id))->handle();

    expect(Company::count())->toBe(1)
        ->and($import->fresh()->total_rows)->toBe(1);
});

test('a queued import says it is waiting instead of showing an empty result table', function () {
    Storage::fake('local');

    $actor = superAdmin();
    $import = stageImport($actor, "name\nAcme\n");

    Livewire::actingAs($actor)
        ->test(ImportPage::class, ['import' => $import->id])
        ->assertSee('Waiting for a queue worker')
        ->assertDontSeeLivewire(ImportRowsTable::class);

    (new ImportTableJob($import->id))->handle();

    Livewire::actingAs($actor)
        ->test(ImportPage::class, ['import' => $import->id])
        ->assertDontSee('Waiting for a queue worker')
        ->assertSeeLivewire(ImportRowsTable::class);
});

test('acknowledging a finished import deletes the file and keeps the results', function () {
    Storage::fake('local');

    $actor = superAdmin();
    $import = stageImport($actor, "name\nAcme\n");
    $path = $import->path;

    (new ImportTableJob($import->id))->handle();

    Livewire::actingAs($actor)
        ->test(ImportPage::class, ['import' => $import->id])
        ->call('acknowledge');

    $import->refresh();

    expect(Storage::disk('local')->exists($path))->toBeFalse()
        ->and($import->acknowledged_at)->not->toBeNull()
        ->and($import->rows()->count())->toBe(1)
        ->and(Company::count())->toBe(1);
});

test('an import still running cannot be acknowledged, so a retry keeps its file', function () {
    Storage::fake('local');

    $actor = superAdmin();
    $import = stageImport($actor, "name\nAcme\n");

    $import->update(['status' => Import::STATUS_RUNNING]);
    $import->acknowledge();

    expect(Storage::disk('local')->exists($import->path))->toBeTrue()
        ->and($import->fresh()->acknowledged_at)->toBeNull();
});

test('a stranger cannot reach the page that would acknowledge someone else\'s import', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $import = stageImport($owner, "name\nAcme\n");

    (new ImportTableJob($import->id))->handle();

    Livewire::actingAs($stranger)
        ->test(ImportPage::class, ['import' => $import->id])
        ->assertStatus(404);

    expect(Storage::disk('local')->exists($import->path))->toBeTrue();
});

test('the import page and its rows are refused to anyone but the owner', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $import = stageImport($owner, "name\nAcme\n");

    (new ImportTableJob($import->id))->handle();

    $this->actingAs($stranger)->get(route('imports.show', $import))->assertNotFound();
    $this->actingAs($owner)->get(route('imports.show', $import))->assertOk();

    Livewire::actingAs($stranger)
        ->test(ImportRowsTable::class, ['importId' => $import->id])
        ->assertDontSee('Acme');
});
