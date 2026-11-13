<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;

// "Forgot password" on the website — same email-code flow as the API's
// /auth/forgot-password and /auth/reset-password (PasswordResetService).
// The visitor enters their phone or email; the code goes to the email on
// the account.
class PasswordResetController extends Controller
{
    public function showRequest()
    {
        return view('site.auth.forgot');
    }

    public function sendCode(Request $request, PasswordResetService $resets)
    {
        $data = $request->validate(['identifier' => ['required', 'string', 'max:150']]);

        [$phone, $email] = $this->split($data['identifier']);
        $resets->sendCode($phone, $email);

        return redirect()->route('site.password.reset', ['identifier' => $data['identifier']])
            ->with('status', __('إذا كان للحساب بريد إلكتروني، فقد أرسلنا إليه رمزًا من 6 أرقام صالحًا لمدة 30 دقيقة. إن لم يكن لحسابك بريد إلكتروني، تواصل مع الدعم.'));
    }

    public function showReset(Request $request)
    {
        return view('site.auth.reset', ['identifier' => $request->query('identifier', '')]);
    }

    public function reset(Request $request, PasswordResetService $resets)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:150'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        [$phone, $email] = $this->split($data['identifier']);
        $resets->reset($phone, $email, $data['code'], $data['password']);

        return redirect()->route('site.login')->with('status', __('تم تعيين كلمة المرور الجديدة. سجّل الدخول بها الآن.'));
    }

    /** One box accepts either: anything with "@" is an email, else a phone. */
    private function split(string $identifier): array
    {
        return str_contains($identifier, '@') ? [null, $identifier] : [$identifier, null];
    }
}
