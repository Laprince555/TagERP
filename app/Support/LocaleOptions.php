<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class LocaleOptions
{
    /**
     * @var array<string, array{label: string, native: string}>
     */
    private const SUPPORTED_LOCALES = [
        'en' => [
            'label' => 'English',
            'native' => 'English',
        ],
        'ar' => [
            'label' => 'Arabic',
            'native' => 'العربية',
        ],
    ];

    /**
     * @var array<int, string>
     */
    private const RTL_LOCALES = ['ar'];

    /**
     * @return array<string, array{label: string, native: string}>
     */
    public static function available(): array
    {
        return Cache::rememberForever('locale-options.available', function (): array {
            $discoveredLocales = collect(File::directories(resource_path('lang')))
                ->map(fn (string $path): string => basename($path))
                ->filter(fn (string $locale): bool => array_key_exists($locale, self::SUPPORTED_LOCALES))
                ->values()
                ->all();

            return collect($discoveredLocales)
                ->mapWithKeys(fn (string $locale): array => [$locale => self::SUPPORTED_LOCALES[$locale]])
                ->all();
        });
    }

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, self::available());
    }

    public static function direction(?string $locale = null): string
    {
        return in_array($locale ?? app()->getLocale(), self::RTL_LOCALES, true) ? 'rtl' : 'ltr';
    }

    public static function fallback(): string
    {
        $fallback = config('app.fallback_locale', config('app.locale', 'en'));

        return self::isSupported($fallback) ? $fallback : 'en';
    }
}
