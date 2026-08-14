<?php

use App\Support\RecordReference\ApplicationColor;
use App\Support\RecordReference\RecordReferencePalette;

it('resolves trusted class tokens for every palette color, never raw strings', function (ApplicationColor $color): void {
    $tokens = RecordReferencePalette::resolve($color);

    expect($tokens)->toHaveKeys(['bg', 'text', 'border', 'ring', 'darkBg', 'darkText'])
        ->and($tokens['bg'])->toStartWith('bg-'.$color->value.'-')
        ->and($tokens['darkBg'])->toStartWith('dark:bg-'.$color->value.'-');
})->with(fn () => ApplicationColor::cases());

it('exposes the mandated minimum palette set', function (): void {
    $required = ['sky', 'indigo', 'violet', 'amber', 'emerald', 'rose', 'cyan', 'orange', 'slate'];

    expect(array_map(fn ($case) => $case->value, ApplicationColor::cases()))
        ->toEqual($required);
});
