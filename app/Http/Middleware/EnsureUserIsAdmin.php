<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to authenticated users holding the "admin" role
 * (see database/seeders/RolesAndPermissionsSeeder.php, spatie/laravel-permission).
 * Used on every routes/api.php "admin/*" group and on the admin panel.
 *
 * The admin panel and the public website share one session (the 'web'
 * guard), so a member who is signed in on the website and then opens
 * /admin-panel arrives here as a logged-in non-admin. A bare 403 page is a
 * dead end for that person — send browsers to the admin login instead, where
 * they can sign in with an admin account. JSON clients keep the plain 403.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasRole('admin')) {
            return $next($request);
        }

        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return redirect()->route('admin.login');
        }

        abort(403, 'Admins only.');
    }
}
