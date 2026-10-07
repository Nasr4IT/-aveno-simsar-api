<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to authenticated, non-banned users holding the "admin"
 * role (see database/seeders/RolesAndPermissionsSeeder.php,
 * spatie/laravel-permission). Used on every routes/api.php "admin/*" group
 * and on the /admin-panel (routes/web.php).
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isActiveAdmin()) {
            return $next($request);
        }

        // The only way to hold an /admin-panel session is to have logged in
        // as an admin — so this is one whose role was revoked, or who was
        // banned, since. End the session and send them to the login form
        // with a reason, rather than a 403 page with no way to log out.
        if ($user && ! $request->is('api/*')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['phone' => 'لم تعد تملك صلاحيات الدخول إلى لوحة التحكم.']);
        }

        abort(403, 'Admins only.');
    }
}
