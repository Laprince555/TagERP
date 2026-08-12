<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetUserTheme
{
    /**
     * @var array<int, string>
     */
    private const AVAILABLE_THEMES = [
        'orange-onyx',
        'navy-blue',
        'emerald-dark',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $activeTheme = $request->user()?->theme ?: $request->session()->get('theme', 'orange-onyx');

        if (! in_array($activeTheme, self::AVAILABLE_THEMES, true)) {
            $activeTheme = 'orange-onyx';
        }

        $request->session()->put('theme', $activeTheme);

        View::share('activeTheme', $activeTheme);
        View::share('themeClass', 'theme-'.$activeTheme);

        return $next($request);
    }
}
