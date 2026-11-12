<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Session-based (the 'web' guard) login for the public website — the same
// phone + password, same users table, same rules as Api\Auth\AuthController
// (which hands the Flutter app a Sanctum token instead of a session), so
// one account works on both. Unlike AdminPanel\AdminAuthController, any
// non-banned user may log in here.
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('site.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $credentials['phone'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['phone' => 'بيانات الدخول غير صحيحة.'])->onlyInput('phone');
        }

        if ($user->is_banned) {
            return back()->withErrors(['phone' => 'تم إيقاف هذا الحساب.'])->onlyInput('phone');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('site.home'));
    }

    public function showRegister()
    {
        return view('site.auth.register', ['governorates' => $this->governorates()]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
        ]);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('site.home')->with('status', "أهلاً بك يا {$user->name}! تم إنشاء حسابك.");
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('site.home');
    }

    private function governorates()
    {
        return Governorate::where('is_active', true)
            ->with(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('id')])
            ->orderBy('id')
            ->get();
    }
}
