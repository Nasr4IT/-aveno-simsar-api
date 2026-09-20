<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

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

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // This is a pure JSON API — there is no "login" page to send guests to.
        // ApplicationBuilder::withMiddleware() always pre-registers
        // `redirectGuestsTo(fn () => route('login'))` before this closure
        // runs. Illuminate\Auth\Middleware\Authenticate::unauthenticated()
        // then calls that callback *while constructing* the
        // AuthenticationException, any time `$request->expectsJson()` is
        // false (e.g. Postman/curl without an explicit
        // "Accept: application/json" header) — so without overriding it,
        // building the exception itself crashes with
        // "RouteNotFoundException: Route [login] not defined." before the
        // exception is even thrown. Overriding it to return null stops that.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Second half of the same fix: Handler::unauthenticated() does
        //   $this->shouldReturnJson($request, $e) ? json 401
        //       : redirect()->guest($exception->redirectTo($request) ?? route('login'))
        // Since the exception's redirectTo is now null (see above),
        // `null ?? route('login')` would still crash on the same route if
        // shouldReturnJson() ever fell through to the redirect branch.
        // Forcing every /api/* request down the JSON path — regardless of
        // Accept header — guarantees it never does.
        $exceptions->shouldRenderJsonWhen(function ($request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
