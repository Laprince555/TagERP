<?php

use App\Support\DynamicRecordView\Core\DynamicRecordView;
use App\Support\DynamicRecordView\Resolution\RecordResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\General\System\SubModule;
use Modules\General\System\SubModuleRecordView;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('resolves a record through the authorized query', function (): void {
    $subModule = SubModule::factory()->create();

    $model = app(RecordResolver::class)->resolve(new SubModuleRecordView, $subModule->id);

    expect($model->is($subModule))->toBeTrue();
});

it('404s on a nonexistent id instead of throwing', function (): void {
    app(RecordResolver::class)->resolve(new SubModuleRecordView, 999999);
})->throws(NotFoundHttpException::class);

it('never leaks existence of an unauthorized record — 404 either way', function (): void {
    // A view whose query() excludes everything (simulating an authorization scope).
    $view = new class extends DynamicRecordView
    {
        protected string $viewKey = 'test.locked-out';

        public function model(): string
        {
            return SubModule::class;
        }

        public function query(): Builder
        {
            return SubModule::query()->whereRaw('1 = 0');
        }

        public function title(mixed $record): string
        {
            return '';
        }
    };

    $subModule = SubModule::factory()->create();

    $missingId = 999999;
    $unauthorizedId = $subModule->id;

    $missingThrew = false;
    $unauthorizedThrew = false;

    try {
        app(RecordResolver::class)->resolve($view, $missingId);
    } catch (NotFoundHttpException $e) {
        $missingThrew = true;
    }

    try {
        app(RecordResolver::class)->resolve($view, $unauthorizedId);
    } catch (NotFoundHttpException $e) {
        $unauthorizedThrew = true;
    }

    expect($missingThrew)->toBeTrue()->and($unauthorizedThrew)->toBeTrue();
});

it('memoizes resolution per request — a second resolve() for the same id does not re-query', function (): void {
    $subModule = SubModule::factory()->create();
    $resolver = app(RecordResolver::class);
    $view = new SubModuleRecordView;

    $resolver->resolve($view, $subModule->id);

    DB::enableQueryLog();
    $resolver->resolve($view, $subModule->id);

    expect(DB::getQueryLog())->toBeEmpty();
});
