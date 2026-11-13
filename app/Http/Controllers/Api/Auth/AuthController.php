<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
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
}
