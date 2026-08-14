<?php

namespace App\Support\RecordReference;

/**
 * Resolves a trusted ApplicationColor token into trusted Tailwind utility
 * classes. Classes are written out literally (not interpolated) so
 * Tailwind's content scanner can find them; never build a class name from
 * request/database text.
 *
 * @phpstan-type PaletteTokens array{bg: string, text: string, border: string, ring: string, darkBg: string, darkText: string}
 */
class RecordReferencePalette
{
    /**
     * @return array<string, PaletteTokens>
     */
    protected static function map(): array
    {
        return [
            'sky' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'border' => 'border-sky-200', 'ring' => 'focus:ring-sky-400', 'darkBg' => 'dark:bg-sky-950', 'darkText' => 'dark:text-sky-300'],
            'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-200', 'ring' => 'focus:ring-indigo-400', 'darkBg' => 'dark:bg-indigo-950', 'darkText' => 'dark:text-indigo-300'],
            'violet' => ['bg' => 'bg-violet-50', 'text' => 'text-violet-700', 'border' => 'border-violet-200', 'ring' => 'focus:ring-violet-400', 'darkBg' => 'dark:bg-violet-950', 'darkText' => 'dark:text-violet-300'],
            'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'ring' => 'focus:ring-amber-400', 'darkBg' => 'dark:bg-amber-950', 'darkText' => 'dark:text-amber-300'],
            'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'ring' => 'focus:ring-emerald-400', 'darkBg' => 'dark:bg-emerald-950', 'darkText' => 'dark:text-emerald-300'],
            'rose' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-200', 'ring' => 'focus:ring-rose-400', 'darkBg' => 'dark:bg-rose-950', 'darkText' => 'dark:text-rose-300'],
            'cyan' => ['bg' => 'bg-cyan-50', 'text' => 'text-cyan-700', 'border' => 'border-cyan-200', 'ring' => 'focus:ring-cyan-400', 'darkBg' => 'dark:bg-cyan-950', 'darkText' => 'dark:text-cyan-300'],
            'orange' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'border' => 'border-orange-200', 'ring' => 'focus:ring-orange-400', 'darkBg' => 'dark:bg-orange-950', 'darkText' => 'dark:text-orange-300'],
            'slate' => ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'ring' => 'focus:ring-slate-400', 'darkBg' => 'dark:bg-slate-950', 'darkText' => 'dark:text-slate-300'],
        ];
    }

    /**
     * @return PaletteTokens
     */
    public static function resolve(ApplicationColor $color): array
    {
        return self::map()[$color->value];
    }

    /**
     * Combined class string for the accent block (icon chip background/text/border).
     */
    public static function classes(ApplicationColor $color): string
    {
        $t = self::resolve($color);

        return "{$t['bg']} {$t['text']} {$t['border']} {$t['darkBg']} {$t['darkText']} {$t['ring']}";
    }
}
