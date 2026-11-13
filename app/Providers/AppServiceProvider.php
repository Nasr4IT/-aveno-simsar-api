<?php

namespace App\Providers;

use App\Support\PhoneNumber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // General API limit (applied to the whole api group in
        // bootstrap/app.php): per user when logged in, else per IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));

        // Login, register and password-reset endpoints (API, website and
        // admin panel): 5 attempts a minute per phone/email + IP, so one
        // account can't be brute-forced, and 20 a minute per IP overall, so
        // one client can't spray many accounts. Over the limit → 429.
        RateLimiter::for('auth', function (Request $request) {
            $identifier = PhoneNumber::normalize((string) ($request->input('phone') ?? $request->input('email') ?? ''));

            return [
                Limit::perMinute(5)->by('auth:'.$identifier.'|'.$request->ip()),
                Limit::perMinute(20)->by('auth-ip:'.$request->ip()),
            ];
        });
    }
}
