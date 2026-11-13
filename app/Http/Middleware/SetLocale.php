<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the public website's language (Arabic or English) for this request.
 *
 * The visitor's choice is stored in a long-lived `locale` cookie by
 * Site\LocaleController — a cookie rather than the session, so the choice
 * survives logging out (logout invalidates the session). Arabic is the
 * default (config/app.php 'locale').
 *
 * The admin panel is Arabic-only, so it is always pinned to Arabic
 * regardless of what the visitor picked on the website.
 */
class SetLocale
{
    public const SUPPORTED = ['ar', 'en'];

    public const COOKIE = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->is('admin-panel*')
            ? 'ar'
            : $request->cookie(self::COOKIE, config('app.locale'));

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
