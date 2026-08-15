<?php

use App\Support\DynamicForm\Core\FormDefinitionRegistry;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use App\Support\RecordReference\RecordReferenceRegistry;

/**
 * A queue worker forgets scoped instances between jobs but never re-boots the
 * module providers that fill these registries. Binding them as scoped made
 * every job after the first blow up with "unknown application code".
 */
test('the module registries survive a queue worker forgetting scoped instances', function () {
    expect(app(RecordReferenceRegistry::class)->has('gen-wld-ctr'))->toBeTrue();

    app()->forgetScopedInstances();

    expect(app(RecordReferenceRegistry::class)->has('gen-wld-ctr'))->toBeTrue()
        ->and(app(RecordViewRegistry::class)->has('general.world.country'))->toBeTrue()
        ->and(app(FormDefinitionRegistry::class)->has('general.world.company.create'))->toBeTrue();
});
