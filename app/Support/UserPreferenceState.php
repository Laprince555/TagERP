<?php

namespace App\Support;

use App\Models\User;

class UserPreferenceState
{
    public static function locale(?User $user): string
    {
        return $user?->locale ?: session('locale', LocaleOptions::fallback());
    }

    public static function theme(?User $user, string $fallback = 'orange-onyx'): string
    {
        return $user?->theme ?: session('theme', $fallback);
    }

    public static function persistLocale(string $locale, ?User $user): void
    {
        session()->put('locale', $locale);

        if ($user) {
            $user->forceFill([
                'locale' => $locale,
            ])->save();
        }
    }

    public static function persistTheme(string $theme, ?User $user): void
    {
        session()->put('theme', $theme);

        if ($user) {
            $user->forceFill([
                'theme' => $theme,
            ])->save();
        }
    }
}
