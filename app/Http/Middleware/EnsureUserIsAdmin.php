<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to authenticated users holding the "admin" role
 * (see database/seeders/RolesAndPermissionsSeeder.php, spatie/laravel-permission).
 * Used on every routes/api.php "admin/*" group.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasRole('admin')) {
            abort(403, 'Admins only.');
        }

        return $next($request);
    }
}
