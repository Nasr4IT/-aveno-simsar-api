<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * "Forgot password" by email, shared by the API and the website.
 *
 * The user identifies themselves by phone or email; a 6-digit code is
 * emailed to the address on their account (email is optional at sign-up, so
 * accounts without one can't use this and must contact support). The code
 * is stored hashed in Laravel's password_reset_tokens table, keyed by email,
 * and expires after EXPIRES_MINUTES. A successful reset revokes every API
 * token, so other devices are signed out.
 *
 * sendCode() never reveals whether an account exists (same response either
 * way); brute-forcing the code is stopped by the 'auth' rate limiter on the
 * routes (5 tries a minute) plus the expiry.
 */
class PasswordResetService
{
    public const EXPIRES_MINUTES = 30;

    public function sendCode(?string $phone, ?string $email): void
    {
        $user = $this->findUser($phone, $email);

        if (! $user || ! $user->email || $user->is_banned) {
            return;
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($code), 'created_at' => now()],
        );

        try {
            $user->notify(new PasswordResetCode($code, self::EXPIRES_MINUTES));
        } catch (Throwable $e) {
            // A mail-server problem must not turn into a 500 that also
            // reveals the account exists. Logged for the admin instead.
            report($e);
        }
    }

    public function reset(?string $phone, ?string $email, string $code, string $password): User
    {
        $user = $this->findUser($phone, $email);
        $row = $user?->email ? DB::table('password_reset_tokens')->where('email', $user->email)->first() : null;

        $valid = $row
            && now()->subMinutes(self::EXPIRES_MINUTES)->lte($row->created_at)
            && Hash::check($code, $row->token);

        if (! $valid) {
            throw ValidationException::withMessages(['code' => __('الرمز غير صحيح أو منتهي الصلاحية.')]);
        }

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $user->forceFill(['password' => Hash::make($password)])->save();
        $user->tokens()->delete();

        return $user;
    }

    private function findUser(?string $phone, ?string $email): ?User
    {
        if (filled($email)) {
            return User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->first();
        }

        if (filled($phone)) {
            return User::where('phone', PhoneNumber::normalize($phone))->first();
        }

        return null;
    }
}
