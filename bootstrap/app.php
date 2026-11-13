<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        // 120 requests/minute per user (or IP) on every /api route — the
        // 'api' limiter is defined in AppServiceProvider. Stricter 'auth'
        // limits sit on the login/register/reset routes themselves.
        $middleware->throttleApi();

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // Arabic/English for the public website (cookie-driven; the admin
        // panel is pinned to Arabic inside the middleware). Web group only —
        // the JSON API's language is untouched.
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // Three surfaces, three guest behaviours: the JSON API gets a plain
        // 401 (no redirect), a guest hitting the admin panel goes to its own
        // login, and a guest hitting a members-only page on the public
        // website (routes/site.php) goes to the site's login. Without this
        // override, the framework default `route('login')` would also be
        // wrong for the admin panel and for api/* (JSON clients must never
        // be redirected).
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*')) {
                return null;
            }

            return $request->is('admin-panel*') ? route('admin.login') : route('site.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Second half of the same fix: Handler::unauthenticated() does
        //   $this->shouldReturnJson($request, $e) ? json 401
        //       : redirect()->guest($exception->redirectTo($request) ?? route('login'))
        // For api/* the exception's redirectTo is null (see above), so the
        // `?? route('login')` fallback would still crash on that
        // still-undefined route name if shouldReturnJson() ever fell
        // through to the redirect branch. Forcing every /api/* request down
        // the JSON path — regardless of Accept header — guarantees it never
        // does. (The admin panel's own redirectTo is never null, so it
        // never reaches this fallback at all.)
        $exceptions->shouldRenderJsonWhen(function ($request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // No-ops entirely without SENTRY_LARAVEL_DSN set (the SDK's own
        // behavior, not a check we have to write) — safe to leave wired up
        // in every environment, including local dev with no DSN configured.
        Integration::handles($exceptions);
    })->create();
