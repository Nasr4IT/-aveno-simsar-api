<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks the session (and CSRF) cookie Secure on any request that came in
 * over https — including through Render's TLS-terminating proxy, see
 * trustProxies() in bootstrap/app.php — so the /admin-panel's login cookie
 * is never sent back over plain http, without anyone having to remember
 * SESSION_SECURE_COOKIE per environment. An explicit SESSION_SECURE_COOKIE
 * still wins. Runs before StartSession, which reads the setting when it
 * writes the cookie.
 */
class SecureSessionCookieOverHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isSecure() && config('session.secure') === null) {
            config(['session.secure' => true]);
        }

        return $next($request);
    }
}
