<?php

use App\Livewire\DynamicForm\Form;
use App\Livewire\DynamicForm\FormModal;
use Livewire\Livewire;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\Livewire\World\Companies\CompaniesTable;
use Modules\General\Models\World\Companies\Company;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

/**
 * Proof-of-concept coverage for the DynamicForm engine (Phase 1-4): field
 * rendering, the bounded relation-list picker (first 5 + search + load
 * more), validation, save, and the Dynamic Table "Create" toolbar button
 * wiring, all exercised through the first real definition, CompanyForm.
 */
beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();

    $this->actingAs(superAdmin());
});

it('shows an Add Company button on the companies table wired to its form key', function (): void {
    Livewire::test(CompaniesTable::class)
        ->assertSee('Add Company')
        ->assertSeeHtml('open-form-modal.general.world.company.create');
});

it('relays the toolbar open event into the event the modal listens for', function (): void {
    Livewire::test(FormModal::class, ['formKey' => 'general.world.company.create'])
        ->dispatch('open-form-modal.general.world.company.create')
        ->assertDispatched('form-modal-opened.general.world.company.create');
});

it('renders every declared field for the company form', function (): void {
    Livewire::test(Form::class, ['formKey' => 'general.world.company.create'])
        ->assertSee('Name')
        ->assertSee('Tax ID')
        ->assertSee('City')
        ->assertSee('Address')
        ->assertSee('Phone')
        ->assertSee('Email')
        ->assertSee('Website');
});

it('rejects an empty required field', function (): void {
    Livewire::test(Form::class, ['formKey' => 'general.world.company.create'])
        ->set('data.name', '')
        ->call('save');

    expect(Company::count())->toBe(0);
});

it('creates a Company with a generated code and dispatches the saved event', function (): void {
    Livewire::test(Form::class, ['formKey' => 'general.world.company.create'])
        ->set('data.name', 'Acme Corp')
        ->set('data.tax_id', '999')
        ->set('data.email', 'contact@acme.test')
        ->call('save')
        ->assertDispatched('dynamic-form-saved.general.world.company.create');

    $company = Company::where('name', 'Acme Corp')->firstOrFail();

    expect($company->code)->toBe('gen-wld-com-acme-corp-999')
        ->and($company->email)->toBe('contact@acme.test');
});

it('saves a city chosen through the Country|State|City cascade', function (): void {
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);
    $state = State::create(['name' => 'Alexandria', 'country_id' => $country->id, 'country_code' => 'EG']);
    $city = City::create(['name' => 'Agami', 'country_id' => $country->id, 'state_id' => $state->id, 'country_code' => 'EG']);

    Livewire::test(Form::class, ['formKey' => 'general.world.company.create'])
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $country->id)
        ->call('chooseCascade', 'city', 'state', $state->id)
        ->call('chooseCascade', 'city', 'city', $city->id)
        ->set('data.name', 'Acme Corp')
        ->call('save');

    expect(Company::where('name', 'Acme Corp')->firstOrFail()->city_id)->toBe($city->id);
});
