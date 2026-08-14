<?php

use App\Support\DynamicRecordView\Core\Exceptions\DuplicateRecordViewKeyException;
use App\Support\DynamicRecordView\Core\Exceptions\UnknownRecordViewKeyException;
use App\Support\DynamicRecordView\Core\RecordViewRegistry;
use Modules\General\System\SubModuleRecordView;

it('resolves a registered key to its view class', function (): void {
    $registry = new RecordViewRegistry;
    $registry->register('general.sub-module', SubModuleRecordView::class);

    expect($registry->has('general.sub-module'))->toBeTrue()
        ->and($registry->resolve('general.sub-module'))->toBe(SubModuleRecordView::class);
});

it('rejects registering the same key twice', function (): void {
    $registry = new RecordViewRegistry;
    $registry->register('general.sub-module', SubModuleRecordView::class);
    $registry->register('general.sub-module', SubModuleRecordView::class);
})->throws(DuplicateRecordViewKeyException::class);

it('fails safely resolving an unknown key', function (): void {
    $registry = new RecordViewRegistry;
    $registry->resolve('does.not.exist');
})->throws(UnknownRecordViewKeyException::class);

it('reports has() false for an unknown key', function (): void {
    $registry = new RecordViewRegistry;

    expect($registry->has('nope'))->toBeFalse();
});
