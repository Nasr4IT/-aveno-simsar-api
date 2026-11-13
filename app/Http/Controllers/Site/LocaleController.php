<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

// The language switch in the site's top bar. Remembers the choice for a
// year in a cookie (read back by the SetLocale middleware) and returns the
// visitor to the page they were on.
class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale)
    {
        abort_unless(in_array($locale, SetLocale::SUPPORTED, true), 404);

        return redirect()->back(fallback: route('site.home'))
            ->withCookie(cookie()->forever(SetLocale::COOKIE, $locale));
    }
}
