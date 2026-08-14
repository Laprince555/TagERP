<?php

namespace App\Http\Middleware;

use App\Support\LocaleOptions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userLocale = $request->user()?->locale;
        $sessionLocale = $request->session()->get('locale');
        $queryLocale = $request->query('locale', $request->query('lang'));
        $segmentLocale = $request->segment(1);

        $locale = collect([
            $userLocale,
            $sessionLocale,
            $queryLocale,
            $segmentLocale,
            LocaleOptions::fallback(),
        ])->first(fn (mixed $candidate): bool => LocaleOptions::isSupported($candidate), LocaleOptions::fallback());

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        View::share('activeLocale', $locale);
        View::share('availableLocales', LocaleOptions::available());

        return $next($request);
    }
}
