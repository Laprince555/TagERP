<?php

use App\Support\RecordReference\ApplicationColor;
use App\Support\RecordReference\RecordReferenceProvider;
use App\Support\RecordReference\RecordReferenceRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

function fakeProvider(string $code): RecordReferenceProvider
{
    return new class($code) implements RecordReferenceProvider
    {
        public function __construct(private string $code) {}

        public function applicationCode(): string
        {
            return $this->code;
        }

        public function modelClass(): string
        {
            return Model::class;
        }

        public function identityColumns(): array
        {
            return ['id'];
        }

        public function cardColumns(): array
        {
            return [];
        }

        public function previewColumns(): array
        {
            return [];
        }

        public function title(Model $record): string
        {
            return 'fake';
        }

        public function url(Model $record): ?string
        {
            return null;
        }

        public function cardFacts(Model $record): array
        {
            return [];
        }

        public function previewFacts(Model $record): array
        {
            return [];
        }

        public function scopeQuery(Builder $query): Builder
        {
            return $query;
        }

        public function authorize(Model $record): bool
        {
            return true;
        }

        public function cacheTtl(): ?int
        {
            return null;
        }
    };
}

it('resolves a registered provider by its application code', function (): void {
    $registry = new RecordReferenceRegistry;
    $registry->register(fakeProvider('gen-wld-ctr'));

    expect($registry->resolve('gen-wld-ctr'))->not->toBeNull()
        ->and($registry->has('gen-wld-ctr'))->toBeTrue();
});

it('never resolves a code no provider registered for, forged or otherwise', function (): void {
    $registry = new RecordReferenceRegistry;

    expect($registry->resolve('not-a-real-code'))->toBeNull()
        ->and($registry->has('not-a-real-code'))->toBeFalse();
});

it('refuses to register two providers for the same application code', function (): void {
    $registry = new RecordReferenceRegistry;
    $registry->register(fakeProvider('gen-wld-ctr'));
    $registry->register(fakeProvider('gen-wld-ctr'));
})->throws(InvalidArgumentException::class);

it('exposes the full documented palette allowlist', function (): void {
    expect(ApplicationColor::tryFrom('sky'))->not->toBeNull()
        ->and(ApplicationColor::tryFrom('not-a-color'))->toBeNull();
});
