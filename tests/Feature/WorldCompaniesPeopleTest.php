<?php

use Livewire\Livewire;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\Livewire\World\Companies\CompaniesTable;
use Modules\General\Livewire\World\People\PeopleTable;
use Modules\General\Livewire\World\People\PersonPositionsTable;
use Modules\General\Models\World\Companies\Company;
use Modules\General\Models\World\People\Person;
use Modules\General\Models\World\People\PersonPosition;
use Modules\General\System\Application;

/**
 * Smoke coverage for the Company/Person vertical slices: index render,
 * hierarchical code generation, Company tag on the embedded Positions
 * table, show route, and access denial â€” mirrors
 * tests/Feature/WorldReferenceDataTest.php.
 */
beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();

    $this->actingAs(superAdmin());
});

it('generates a hierarchical code and a companyname-taxid slug on create', function (): void {
    $company = Company::factory()->create(['name' => 'Acme Corp', 'tax_id' => '12345']);

    expect($company->slug)->toBe('acme-corp-12345')
        ->and($company->code)->toBe('gen-wld-com-acme-corp-12345');
});

it('renders the companies index page and allows search', function (): void {
    Company::factory()->create(['name' => 'Acme Corp']);
    Company::factory()->create(['name' => 'Globex Inc']);

    $this->get(route('general.world.companies'))
        ->assertSuccessful()
        ->assertSee('Companies')
        ->assertSee('gen-wld-com')
        ->assertSeeLivewire(CompaniesTable::class);

    Livewire::test(CompaniesTable::class)
        ->assertSee('Acme Corp')
        ->assertSee('Globex Inc')
        ->set('search', 'Acme')
        ->call('submitSearch')
        ->assertSee('Acme Corp')
        ->assertDontSee('Globex Inc');
});

it('renders the companies show route and denies access when the application is inactive', function (): void {
    $company = Company::factory()->create(['name' => 'Acme Corp']);

    $this->get(route('general.world.companies.show', ['recordId' => $company->id]))
        ->assertOk()
        ->assertSee('Acme Corp');

    Application::where('code', 'gen-wld-com')->first()->update(['is_active' => false]);

    $this->get(route('general.world.companies.show', ['recordId' => $company->id]))
        ->assertNotFound();
});

it('renders the people index page and allows search', function (): void {
    Person::factory()->create(['full_name' => 'John Doe']);
    Person::factory()->create(['full_name' => 'Jane Smith']);

    $this->get(route('general.world.people'))
        ->assertSuccessful()
        ->assertSee('People')
        ->assertSee('gen-wld-per')
        ->assertSeeLivewire(PeopleTable::class);

    Livewire::test(PeopleTable::class)
        ->assertSee('John Doe')
        ->assertSee('Jane Smith')
        ->set('search', 'John')
        ->call('submitSearch')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

it('renders the people show route and denies access when the application is inactive', function (): void {
    $person = Person::factory()->create(['full_name' => 'John Doe']);

    $this->get(route('general.world.people.show', ['recordId' => $person->id]))
        ->assertOk()
        ->assertSee('John Doe');

    Application::where('code', 'gen-wld-per')->first()->update(['is_active' => false]);

    $this->get(route('general.world.people.show', ['recordId' => $person->id]))
        ->assertNotFound();
});

it('shows a person\'s position history with the company rendered as a tag', function (): void {
    $company = Company::factory()->create(['name' => 'Acme Corp']);
    $person = Person::factory()->create(['full_name' => 'John Doe']);
    $position = PersonPosition::factory()->create([
        'person_id' => $person->id,
        'company_id' => $company->id,
        'position' => 'Senior Engineer',
    ]);

    expect($position->code)->toBe($person->code.'-positions-'.$position->slug);

    Livewire::test(PersonPositionsTable::class, [
        'embedRecordViewKey' => 'general.world.person',
        'embedRecordId' => $person->id,
        'embedSection' => 'other-data',
        'embedTab' => 'positions',
        'embedContent' => 'positions-table',
    ])
        ->assertSee('Senior Engineer')
        ->assertSeeHtml(route('general.world.companies.show', ['recordId' => $company->id]));
});
