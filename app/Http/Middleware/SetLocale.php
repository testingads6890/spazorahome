<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;

/** Uses the visitor's chosen language (a plain cookie), falling back to the configured default. */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $code = (string) $request->cookie(Locales::COOKIE);

        // Always set it, so a locale chosen by one request can never leak into the next in a long-lived worker.
        app()->setLocale($code !== '' && Locales::isAvailable($code) ? $code : config('app.locale'));

        return $next($request);
    }
}
