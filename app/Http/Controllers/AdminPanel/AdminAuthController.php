<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Session-based (the 'web' guard) — deliberately separate from the
// Sanctum bearer-token auth the Flutter app and routes/api.php use. This
// is a traditional server-rendered login for a human using a browser; the
// API's token auth isn't a fit for that.
class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $data['phone'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->hasRole('admin')) {
            return back()->withErrors(['phone' => 'بيانات الدخول غير صحيحة أو لا تملك صلاحيات إدارية.'])->onlyInput('phone');
        }

        if ($user->is_banned) {
            return back()->withErrors(['phone' => 'تم إيقاف هذا الحساب.']);
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
