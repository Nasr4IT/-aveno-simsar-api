<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AccountService;
use App\Services\PasswordResetService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * See docs/API_CONTRACT.md § Auth.
 * The Flutter app authenticates with a Sanctum personal access token
 * (bearer token, no cookies) — call login/register once, store the
 * returned token, send it as "Authorization: Bearer <token>" after that.
 */
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->merge(['phone' => PhoneNumber::normalize($request->input('phone'))]);

        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', PhoneNumber::RULE, 'unique:users,phone'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
        ])->validate();

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        return $this->tokenResponse($user, 201);
    }

    // {user, token}. The new user becomes the request's viewer first, so
    // UserResource shows them their own phone/email (contact details are
    // private to the owner).
    private function tokenResponse(User $user, int $status = 200)
    {
        auth('sanctum')->setUser($user);

        return response()->json([
            'user' => new UserResource($user->load(['governorate', 'city'])),
            'token' => $user->createToken('mobile')->plainTextToken,
        ], $status);
    }

    public function login(Request $request)
    {
        $request->merge(['phone' => PhoneNumber::normalize($request->input('phone'))]);

        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $credentials['phone'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 422);
        }

        if ($user->is_banned) {
            return response()->json(['message' => 'تم إيقاف هذا الحساب'], 403);
        }

        return $this->tokenResponse($user);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load(['governorate', 'city']));
    }

    // PUT /auth/me (JSON) or POST /auth/me (multipart — PHP doesn't parse
    // multipart bodies on PUT, so send the avatar with POST). Partial: only
    // the fields sent are changed.
    public function updateMe(Request $request, AccountService $accounts)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'governorate_id' => ['sometimes', 'nullable', 'exists:governorates,id'],
            'city_id' => ['sometimes', 'nullable', 'exists:cities,id'],
            'avatar' => ['sometimes', 'image', 'max:5120'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ]);

        $accounts->updateProfile($user, $data, $request->file('avatar'), $request->boolean('remove_avatar'));

        return new UserResource($user);
    }

    // POST /auth/change-password — signs out every other device.
    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password:sanctum'],
            'password' => ['required', 'string', 'min:8', 'different:current_password'],
        ]);

        $user = $request->user();
        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => __('تم تغيير كلمة المرور.')]);
    }

    // DELETE /auth/me — required by Google Play / App Store for any app with
    // sign-up. Irreversible; see AccountService::delete for what is removed.
    public function destroyMe(Request $request, AccountService $accounts)
    {
        $request->validate(['password' => ['required', 'string', 'current_password:sanctum']]);

        $accounts->delete($request->user());

        return response()->json(['message' => __('تم حذف حسابك.')]);
    }

    // POST /auth/forgot-password {phone} or {email}. Always the same answer,
    // whether or not the account exists (no account enumeration).
    public function forgotPassword(Request $request, PasswordResetService $resets)
    {
        $data = $request->validate([
            'phone' => ['required_without:email', 'nullable', 'string'],
            'email' => ['required_without:phone', 'nullable', 'email'],
        ]);

        $resets->sendCode($data['phone'] ?? null, $data['email'] ?? null);

        return response()->json(['message' => __('إذا كان للحساب بريد إلكتروني، فقد أرسلنا إليه رمزًا من 6 أرقام صالحًا لمدة 30 دقيقة. إن لم يكن لحسابك بريد إلكتروني، تواصل مع الدعم.')]);
    }

    // POST /auth/reset-password {phone|email, code, password}. Revokes all
    // tokens; the app then logs in again with the new password.
    public function resetPassword(Request $request, PasswordResetService $resets)
    {
        $data = $request->validate([
            'phone' => ['required_without:email', 'nullable', 'string'],
            'email' => ['required_without:phone', 'nullable', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $resets->reset($data['phone'] ?? null, $data['email'] ?? null, $data['code'], $data['password']);

        return response()->json(['message' => __('تم تعيين كلمة المرور الجديدة. سجّل الدخول بها الآن.')]);
    }
}
