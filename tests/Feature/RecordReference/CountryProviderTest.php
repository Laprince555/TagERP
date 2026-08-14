<?php

use App\Models\User;
use App\Support\RecordReference\RecordReferenceAccess;
use App\Support\RecordReference\RecordReferenceRegistry;
use Modules\General\Database\Seeders\System\ModulesSeeder;
use Modules\General\Database\Seeders\System\SubModulesSeeder;
use Modules\General\Database\Seeders\World\WorldApplicationsSeeder;
use Modules\General\System\Application;
use Nnjeim\World\Models\Country;

beforeEach(function (): void {
    (new ModulesSeeder)->run();
    (new SubModulesSeeder)->run();
    (new WorldApplicationsSeeder)->run();
});

function makeCountry(array $overrides = []): Country
{
    return Country::create(array_merge([
        'name' => 'Egypt',
        'iso2' => 'EG',
        'iso3' => 'EGY',
        'phone_code' => '20',
        'region' => 'Africa',
        'subregion' => 'Northern Africa',
        'status' => 1,
    ], $overrides));
}

it('is registered under its immutable application code', function (): void {
    $provider = app(RecordReferenceRegistry::class)->resolve('gen-wld-ctr');

    expect($provider)->not->toBeNull()
        ->and($provider->applicationCode())->toBe('gen-wld-ctr')
        ->and($provider->modelClass())->toBe(Country::class);
});

it('builds the title, route url, and prioritized facts from real Country columns', function (): void {
    $country = makeCountry();
    $provider = app(RecordReferenceRegistry::class)->resolve('gen-wld-ctr');

    expect($provider->title($country))->toBe('Egypt')
        ->and($provider->url($country))->toBe(route('general.world.countries.show', ['recordId' => $country->id]));

    $facts = $provider->cardFacts($country);
    expect($facts)->not->toBeEmpty();
    expect(collect($facts)->pluck('label')->all())->toContain('Region');
});

it('authorizes a reference only for an active (status = 1) Country record', function (): void {
    $provider = app(RecordReferenceRegistry::class)->resolve('gen-wld-ctr');

    expect($provider->authorize(makeCountry(['status' => 1])))->toBeTrue()
        ->and($provider->authorize(makeCountry(['status' => 0])))->toBeFalse();
});

it('the shared access boundary denies an inactive Application regardless of record status', function (): void {
    $this->actingAs(User::factory()->create());

    $country = makeCountry();
    $provider = app(RecordReferenceRegistry::class)->resolve('gen-wld-ctr');
    $access = app(RecordReferenceAccess::class);

    $application = Application::where('code', 'gen-wld-ctr')->first();
    expect($access->recordAccessible($provider, $application, $country))->toBeTrue();

    $application->update(['is_active' => false]);
    $application->refresh();
    expect($access->recordAccessible($provider, $application, $country))->toBeFalse();
});

it('the shared access boundary denies a guest', function (): void {
    $country = makeCountry();
    $provider = app(RecordReferenceRegistry::class)->resolve('gen-wld-ctr');
    $access = app(RecordReferenceAccess::class);
    $application = Application::where('code', 'gen-wld-ctr')->first();

    expect($access->recordAccessible($provider, $application, $country))->toBeFalse();
});
