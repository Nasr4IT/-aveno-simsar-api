<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

// Session-based (the 'web' guard) — deliberately separate from the
// Sanctum bearer-token auth the Flutter app and routes/api.php use. This
// is a traditional server-rendered login for a human using a browser; the
// API's token auth isn't a fit for that.
class AdminAuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            if ($request->user()->isActiveAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            // A session that lost the admin role (or was banned) since
            // logging in — end it here, otherwise this page would bounce
            // straight back to the panel and its 403.
            $this->endSession($request);
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Keyed per phone + IP (the same scheme as Laravel Breeze's login),
        // so guessing an admin's password is slowed to a crawl without a
        // stranger being able to lock the real admin out from elsewhere.
        $throttleKey = 'admin-login|'.$data['phone'].'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return back()->withErrors([
                'phone' => 'محاولات دخول كثيرة. حاول مرة أخرى بعد '.RateLimiter::availableIn($throttleKey).' ثانية.',
            ])->onlyInput('phone');
        }

        $user = User::where('phone', $data['phone'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->hasRole('admin')) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            return back()->withErrors(['phone' => 'بيانات الدخول غير صحيحة أو لا تملك صلاحيات إدارية.'])->onlyInput('phone');
        }

        RateLimiter::clear($throttleKey);

        if ($user->is_banned) {
            return back()->withErrors(['phone' => 'تم إيقاف هذا الحساب.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $this->endSession($request);

        return redirect()->route('admin.login');
    }

    private function endSession(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
