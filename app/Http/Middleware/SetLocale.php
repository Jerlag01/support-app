<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = array_keys(config('app.locales'));

        $locale = $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? $request->getPreferredLanguage($locales)
            ?? config('app.locale');

        app()->setLocale(in_array($locale, $locales, true) ? $locale : config('app.locale'));

        return $next($request);
    }
}
