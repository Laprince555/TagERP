<?php

use App\Support\RecordReference\ApplicationColor;
use App\Support\RecordReference\RecordFact;
use App\Support\RecordReference\RecordReferenceIdentity;
use Illuminate\Support\Facades\Blade;

function makeIdentity(array $overrides = []): RecordReferenceIdentity
{
    return new RecordReferenceIdentity(
        applicationCode: $overrides['applicationCode'] ?? 'gen-wld-ctr',
        applicationName: $overrides['applicationName'] ?? 'Countries',
        applicationColor: $overrides['applicationColor'] ?? ApplicationColor::Sky,
        applicationIcon: $overrides['applicationIcon'] ?? 'globe-alt',
        recordKey: $overrides['recordKey'] ?? 1,
        authorized: $overrides['authorized'] ?? true,
        title: $overrides['title'] ?? 'Egypt',
        url: array_key_exists('url', $overrides) ? $overrides['url'] : '/general/world/countries/1/view',
    );
}

it('renders the card variant with the application name, record title, and facts', function (): void {
    $identity = makeIdentity();
    $facts = [new RecordFact('Region', 'Africa', 10)];

    $html = Blade::render('<x-record-reference.card :identity="$identity" :facts="$facts" />', compact('identity', 'facts'));

    expect($html)->toContain('Egypt')
        ->toContain('Countries')
        ->toContain('Africa')
        ->toContain($identity->url);
});

it('renders the tag variant as a compact chip with a context-preview trigger, not an inline fact fetch', function (): void {
    $identity = makeIdentity();

    $html = Blade::render('<x-record-reference.tag :identity="$identity" />', compact('identity'));

    expect($html)->toContain('Egypt')
        ->toContain('open-preview')
        ->not->toContain('Region'); // no preview facts leaked into initial markup
});

it('renders the icon variant with an accessible label and a delayed-hover preview trigger', function (): void {
    $identity = makeIdentity();

    $html = Blade::render('<x-record-reference.icon :identity="$identity" />', compact('identity'));

    expect($html)->toContain('aria-label="Egypt (Countries)"')
        ->toContain('scheduleOpen')
        ->not->toContain('Region');
});

it('escapes a hostile record title instead of emitting raw HTML', function (): void {
    $identity = makeIdentity(['title' => '<script>alert(1)</script>']);

    $html = Blade::render('<x-record-reference.card :identity="$identity" :facts="[]" />', ['identity' => $identity, 'facts' => []]);

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->toContain('&lt;script&gt;');
});

it('renders a non-link fallback and never href="" when no url is available', function (): void {
    $identity = makeIdentity(['url' => null]);

    $html = Blade::render('<x-record-reference.tag :identity="$identity" />', compact('identity'));

    expect($html)->not->toContain('href=""')
        ->not->toContain('<a ')
        ->toContain('Egypt');
});
