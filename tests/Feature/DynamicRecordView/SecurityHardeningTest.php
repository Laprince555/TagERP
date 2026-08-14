<?php

use App\Livewire\DynamicRecordView\OtherData;
use App\Models\User;
use App\Support\DynamicRecordView\Core\Fields\LinkViewField;
use Livewire\Livewire;
use Modules\General\Livewire\SubModuleRecordView;
use Modules\General\System\SubModule;

/**
 * Phase 8 security hardening: LinkViewField protocol allowlist, and
 * render-time self-heal of a forged/stale activeTab so a direct
 * Livewire::test()->set() can never leave unauthorized/invalid tab content
 * on screen through a render cycle.
 */
beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

// --- LinkViewField URL protocol allowlist ---

it('allows safe LinkViewField schemes', function (string $url): void {
    expect(LinkViewField::isSafeUrl($url))->toBeTrue();
})->with([
    'https://example.com',
    'http://example.com',
    'mailto:test@example.com',
    'tel:+1234567890',
    '/internal/relative/path',
]);

it('rejects unsafe LinkViewField schemes', function (string $url): void {
    expect(LinkViewField::isSafeUrl($url))->toBeFalse();
})->with([
    'javascript:alert(1)',
    'JavaScript:alert(1)',
    'data:text/html,<script>alert(1)</script>',
    'vbscript:msgbox(1)',
    "javascript:alert(1)\n",
]);

it('getUrl() returns null instead of an unsafe URL', function (): void {
    $field = LinkViewField::make('link')->linkUsing(fn () => 'javascript:alert(1)');

    expect($field->getUrl(null))->toBeNull();
});

it('getUrl() returns a safe URL unchanged', function (): void {
    $field = LinkViewField::make('link')->linkUsing(fn () => 'https://example.com');

    expect($field->getUrl(null))->toBe('https://example.com');
});

// --- Active tab forgery / render-time self-heal ---

it('self-heals a forged activeTab on RecordView render instead of leaving no tab selected', function (): void {
    $subModule = SubModule::factory()->create();

    $component = Livewire::test(SubModuleRecordView::class, ['recordId' => $subModule->id]);

    $component->set('activeTab', 'totally-forged-tab-key');

    // Rendering must not throw, and the component must have corrected itself
    // back to a real authorized tab rather than staying on the forged value.
    $component->assertOk();
    expect($component->get('activeTab'))->not->toBe('totally-forged-tab-key');
});

it('self-heals a forged activeTab on OtherData render', function (): void {
    $subModule = SubModule::factory()->create();

    $component = Livewire::test(OtherData::class, [
        'recordViewKey' => 'general.sub-module',
        'recordId' => $subModule->id,
    ]);

    $component->set('activeTab', 'totally-forged-tab-key');

    $component->assertOk();
    expect($component->get('activeTab'))->not->toBe('totally-forged-tab-key');
});
