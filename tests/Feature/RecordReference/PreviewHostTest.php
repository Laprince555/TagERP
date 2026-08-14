<?php

use App\Livewire\RecordReference\PreviewHost;
use App\Models\User;
use Livewire\Livewire;
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

it('returns an authorized preview with only allowlisted facts, never toArray() of the model', function (): void {
    $this->actingAs(User::factory()->create());
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'phone_code' => '20', 'status' => 1]);

    $result = app(PreviewHost::class)->loadPreview('gen-wld-ctr', (string) $country->id);

    expect($result['available'])->toBeTrue()
        ->and($result['title'])->toBe('Egypt')
        ->and($result['facts'])->not->toBeEmpty();

    $allowedKeys = ['label', 'value'];
    foreach ($result['facts'] as $fact) {
        expect(array_keys($fact))->toBe($allowedKeys);
    }
});

it('returns a generic unavailable preview for a guest', function (): void {
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);

    $result = app(PreviewHost::class)->loadPreview('gen-wld-ctr', (string) $country->id);

    expect($result)->toBe(['available' => false, 'status' => null, 'title' => null, 'url' => null, 'facts' => []]);
});

it('returns a generic unavailable preview for a forged application code', function (): void {
    $this->actingAs(User::factory()->create());
    $result = app(PreviewHost::class)->loadPreview('not-a-real-code', '1');

    expect($result)->toBe(['available' => false, 'status' => null, 'title' => null, 'url' => null, 'facts' => []]);
});

it('returns a generic unavailable preview for a missing record id', function (): void {
    $this->actingAs(User::factory()->create());
    $result = app(PreviewHost::class)->loadPreview('gen-wld-ctr', '999999');

    expect($result['available'])->toBeFalse();
});

it('returns a generic unavailable preview once the owning application is inactive, with no partial data leak', function (): void {
    $this->actingAs(User::factory()->create());
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);
    Application::where('code', 'gen-wld-ctr')->update(['is_active' => false]);

    $result = app(PreviewHost::class)->loadPreview('gen-wld-ctr', (string) $country->id);

    expect($result['available'])->toBeFalse()
        ->and($result['title'])->toBeNull()
        ->and($result['facts'])->toBe([]);
});

it('rejects an oversized recordKey/applicationCode instead of querying with it', function (): void {
    $this->actingAs(User::factory()->create());
    $result = app(PreviewHost::class)->loadPreview(str_repeat('a', 100), str_repeat('1', 100));

    expect($result['available'])->toBeFalse();
});

it('is reachable as a real Livewire component (the single shared preview host)', function (): void {
    $this->actingAs(User::factory()->create());
    $country = Country::create(['name' => 'Egypt', 'iso2' => 'EG', 'iso3' => 'EGY', 'phone_code' => '20', 'region' => 'Africa', 'subregion' => 'Northern Africa', 'status' => 1]);

    Livewire::test(PreviewHost::class)
        ->call('loadPreview', 'gen-wld-ctr', (string) $country->id)
        ->assertReturned(fn ($payload) => $payload['available'] === true && $payload['title'] === 'Egypt');
});
