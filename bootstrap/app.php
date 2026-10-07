<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sanctum's cookie mode (EnsureFrontendRequestsAreStateful) is
        // deliberately not on the api group — see 'guard' in config/sanctum.php.

        // Render (see Dockerfile) terminates TLS at its proxy and forwards
        // plain http, which is the only way to reach the app. Trusting the
        // proxy's X-Forwarded-Proto keeps every route() the admin panel
        // builds (form actions, redirects, pagination) on https. Host isn't
        // trusted, so a client-supplied X-Forwarded-Host can't change URLs.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // This is a JSON API for every route except the small server-rendered
        // admin panel (routes/web.php, 'admin-panel/*') — so unauthenticated
        // API requests get a plain 401, while a guest hitting the admin
        // panel gets redirected to its real login page. Without this
        // override entirely, ApplicationBuilder::withMiddleware()'s default
        // `redirectGuestsTo(fn () => route('login'))` crashes the API path
        // with "RouteNotFoundException: Route [login] not defined." — there
        // is no route literally named 'login', only 'admin.login'.
        $middleware->redirectGuestsTo(fn ($request) => $request->is('api/*') ? null : route('admin.login'));
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
