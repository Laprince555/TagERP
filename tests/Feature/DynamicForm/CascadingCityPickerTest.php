<?php

use App\Livewire\DynamicForm\Form;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\Models\World\People\Person;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;

/**
 * The Country|State|City picker. Only city_id is ever persisted — the two
 * upper levels exist purely to make a 150k-row City table pickable.
 */
beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();
    $this->actingAs(User::factory()->create());

    $this->egypt = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);
    $this->saudi = Country::create(['name' => 'Saudi Arabia', 'iso2' => 'SA', 'iso3' => 'SAU', 'phone_code' => '966', 'region' => 'Asia', 'subregion' => 'Western Asia', 'status' => 1]);

    $this->alexandria = State::create(['name' => 'Alexandria', 'country_id' => $this->egypt->id, 'country_code' => 'EG']);
    $this->cairoState = State::create(['name' => 'Cairo Governorate', 'country_id' => $this->egypt->id, 'country_code' => 'EG']);
    $this->riyadhState = State::create(['name' => 'Riyadh Province', 'country_id' => $this->saudi->id, 'country_code' => 'SA']);

    $this->agami = City::create(['name' => 'Agami', 'country_id' => $this->egypt->id, 'state_id' => $this->alexandria->id, 'country_code' => 'EG']);
    $this->borg = City::create(['name' => 'Borg El Arab', 'country_id' => $this->egypt->id, 'state_id' => $this->alexandria->id, 'country_code' => 'EG']);
    $this->cairo = City::create(['name' => 'Cairo', 'country_id' => $this->egypt->id, 'state_id' => $this->cairoState->id, 'country_code' => 'EG']);
    $this->riyadh = City::create(['name' => 'Riyadh', 'country_id' => $this->saudi->id, 'state_id' => $this->riyadhState->id, 'country_code' => 'SA']);
});

function personForm(): Testable
{
    return Livewire::test(Form::class, ['formKey' => 'general.world.person.create']);
}

it('labels the trigger button with the level names, then with the chosen path', function (): void {
    $component = personForm();

    $component->assertSee('Country|State|City');

    $component
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $this->egypt->id)
        ->assertSee('Egypt|State|City')
        ->call('chooseCascade', 'city', 'state', $this->alexandria->id)
        ->assertSee('Egypt|Alexandria|City')
        ->call('chooseCascade', 'city', 'city', $this->agami->id)
        ->assertSee('Egypt|Alexandria|Agami');
});

/**
 * The picker is server-rendered open state, not a nested <flux:modal>:
 * a dialog nested inside FormModal's already-open dialog is re-created by
 * Livewire's DOM morph on the very request that opens it, so the show
 * event lands on a detached element and nothing appears. These assert the
 * panel is genuinely rendered, which is what actually failed before.
 */
/**
 * Blade does NOT compile @js() inside a *component* attribute — on a
 * <flux:button> it renders as the literal string "@js($fieldKey)", so
 * Livewire cannot parse the call and the click silently does nothing.
 * Asserting the compiled attribute is the only thing that catches it;
 * every server-state test still passed while the button was dead.
 */
it('compiles a callable wire:click onto the trigger button', function (): void {
    $html = personForm()->html();

    expect($html)->toContain("toggleCascadePicker('city')")
        ->and($html)->not->toContain('@js(');
});

it('renders the picker panel only after the trigger is clicked', function (): void {
    $component = personForm();

    expect($component->get('openCascadeField'))->toBe('');
    $component->assertDontSee('Search Country…');

    $component->call('toggleCascadePicker', 'city');

    expect($component->get('openCascadeField'))->toBe('city');
    $component->assertSee('Egypt')->assertSee('Saudi Arabia');
});

it('collapses the panel when the trigger is clicked again', function (): void {
    $component = personForm()
        ->call('toggleCascadePicker', 'city')
        ->call('toggleCascadePicker', 'city');

    expect($component->get('openCascadeField'))->toBe('');
});

it('collapses the panel once the last level is chosen', function (): void {
    $component = personForm()
        ->call('toggleCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $this->egypt->id)
        ->call('chooseCascade', 'city', 'state', $this->alexandria->id);

    expect($component->get('openCascadeField'))->toBe('city');

    $component->call('chooseCascade', 'city', 'city', $this->agami->id);

    expect($component->get('openCascadeField'))->toBe('')
        ->and($component->get('data.city'))->toBe($this->agami->id);
});

it('loads only countries when the picker opens, never states or cities', function (): void {
    $component = personForm()->call('openCascadePicker', 'city');

    $countries = collect($component->get('cascadeResults.city.country'))->pluck('label')->all();

    expect($countries)->toEqualCanonicalizing(['Egypt', 'Saudi Arabia'])
        ->and($component->get('cascadeResults.city.state'))->toBeNull()
        ->and($component->get('cascadeResults.city.city'))->toBeNull();
});

it('locks state and city until their parent is chosen', function (): void {
    $component = personForm()->call('openCascadePicker', 'city');

    expect($component->instance()->cascadeLevelUnlocked('city', 'country'))->toBeTrue()
        ->and($component->instance()->cascadeLevelUnlocked('city', 'state'))->toBeFalse()
        ->and($component->instance()->cascadeLevelUnlocked('city', 'city'))->toBeFalse();

    $component->call('chooseCascade', 'city', 'country', $this->egypt->id);

    expect($component->instance()->cascadeLevelUnlocked('city', 'state'))->toBeTrue()
        ->and($component->instance()->cascadeLevelUnlocked('city', 'city'))->toBeFalse();

    $component->call('chooseCascade', 'city', 'state', $this->alexandria->id);

    expect($component->instance()->cascadeLevelUnlocked('city', 'city'))->toBeTrue();
});

it('refuses to load a level whose parent has not been chosen', function (): void {
    $component = personForm()
        ->call('openCascadePicker', 'city')
        ->call('loadMoreCascade', 'city', 'city');

    expect($component->get('cascadeResults.city.city'))->toBe([]);
});

it('filters states by the chosen country', function (): void {
    $component = personForm()
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $this->egypt->id);

    $states = collect($component->get('cascadeResults.city.state'))->pluck('label')->all();

    expect($states)->toEqualCanonicalizing(['Alexandria', 'Cairo Governorate'])
        ->and($states)->not->toContain('Riyadh Province');
});

it('filters cities by the chosen state', function (): void {
    $component = personForm()
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $this->egypt->id)
        ->call('chooseCascade', 'city', 'state', $this->alexandria->id);

    $cities = collect($component->get('cascadeResults.city.city'))->pluck('label')->all();

    expect($cities)->toEqualCanonicalizing(['Agami', 'Borg El Arab'])
        ->and($cities)->not->toContain('Cairo');
});

it('only sets the persisted value once the last level is chosen', function (): void {
    $component = personForm()
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $this->egypt->id);

    expect($component->get('data.city'))->toBeNull();

    $component->call('chooseCascade', 'city', 'state', $this->alexandria->id);
    expect($component->get('data.city'))->toBeNull();

    $component->call('chooseCascade', 'city', 'city', $this->agami->id);
    expect($component->get('data.city'))->toBe($this->agami->id);
});

it('clears deeper levels when an upper level is changed', function (): void {
    $component = personForm()
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $this->egypt->id)
        ->call('chooseCascade', 'city', 'state', $this->alexandria->id)
        ->call('chooseCascade', 'city', 'city', $this->agami->id);

    expect($component->get('data.city'))->toBe($this->agami->id);

    // Switching country must not leave Egypt's state/city selected.
    $component->call('chooseCascade', 'city', 'country', $this->saudi->id);

    expect($component->get('data.city'))->toBeNull()
        ->and($component->get('cascadeSelected.city.state'))->toBeNull()
        ->and($component->get('cascadeSelected.city.city'))->toBeNull();

    $states = collect($component->get('cascadeResults.city.state'))->pluck('label')->all();
    expect($states)->toEqualCanonicalizing(['Riyadh Province']);
});

it('saves only city_id, never country or state', function (): void {
    personForm()
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $this->egypt->id)
        ->call('chooseCascade', 'city', 'state', $this->alexandria->id)
        ->call('chooseCascade', 'city', 'city', $this->agami->id)
        ->set('data.full_name', 'Ahmed Ali')
        ->call('save');

    $person = Person::where('full_name', 'Ahmed Ali')->firstOrFail();

    expect($person->city_id)->toBe($this->agami->id)
        ->and($person->getAttributes())->not->toHaveKey('country_id')
        ->and($person->getAttributes())->not->toHaveKey('state_id');
});

it('refuses a candidate that was never offered at that level', function (): void {
    $component = personForm()
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $this->egypt->id)
        ->call('chooseCascade', 'city', 'state', $this->alexandria->id)
        // Riyadh belongs to another state and is not in the loaded candidates.
        ->call('chooseCascade', 'city', 'city', $this->riyadh->id);

    expect($component->get('data.city'))->toBeNull();
});

it('narrows a level by search', function (): void {
    $component = personForm()
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $this->egypt->id)
        ->call('chooseCascade', 'city', 'state', $this->alexandria->id)
        ->set('cascadeSearch.city.city', 'Borg');

    $cities = collect($component->get('cascadeResults.city.city'))->pluck('label')->all();

    expect($cities)->toEqualCanonicalizing(['Borg El Arab']);
});

it('lets a chosen level be reopened and re-picked', function (): void {
    $component = personForm()
        ->call('openCascadePicker', 'city')
        ->call('chooseCascade', 'city', 'country', $this->egypt->id)
        ->call('chooseCascade', 'city', 'state', $this->alexandria->id)
        ->call('chooseCascade', 'city', 'city', $this->agami->id)
        ->call('reopenCascadeLevel', 'city', 'state');

    expect($component->get('data.city'))->toBeNull()
        ->and($component->get('cascadeSelected.city.state'))->toBeNull()
        ->and($component->get('cascadeSelected.city.country.label'))->toBe('Egypt');

    $component->call('chooseCascade', 'city', 'state', $this->cairoState->id);
    $cities = collect($component->get('cascadeResults.city.city'))->pluck('label')->all();

    expect($cities)->toEqualCanonicalizing(['Cairo']);
});
