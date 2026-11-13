<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Session-based (the 'web' guard) — deliberately separate from the
// Sanctum bearer-token auth the Flutter app and routes/api.php use. This
// is a traditional server-rendered login for a human using a browser; the
// API's token auth isn't a fit for that.
class AdminAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        // Only an admin skips the form. A member signed in on the public
        // website (same session) sees the form plus a notice, instead of
        // being bounced into the panel and hitting the admin gate.
        if ($request->user()?->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login', ['signedInMember' => $request->user()]);
    }

    public function login(Request $request)
    {
        $request->merge(['phone' => PhoneNumber::normalize($request->input('phone'))]);

        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $data['phone'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->hasRole('admin')) {
            return back()->withErrors(['phone' => __('بيانات الدخول غير صحيحة أو لا تملك صلاحيات إدارية.')])->onlyInput('phone');
        }

        if ($user->is_banned) {
            return back()->withErrors(['phone' => __('تم إيقاف هذا الحساب.')]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
