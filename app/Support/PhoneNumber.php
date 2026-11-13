<?php

namespace App\Support;

/**
 * Syrian mobile numbers are stored in one canonical form: 09XXXXXXXX
 * (10 digits). Users type them many ways — "+963 999 111 222",
 * "00963999111222", "963999111222", "0999-111-222", Arabic-Indic digits —
 * and without normalising, each spelling became a separate account and login
 * failed when the user typed a different spelling than at sign-up.
 *
 * Normalise on every way in (register, login, password reset, on the API,
 * the website and the admin panel) and validate the result with RULE.
 */
class PhoneNumber
{
    /** Validation regex for the canonical form. */
    public const RULE = 'regex:/^09\d{8}$/';

    public static function normalize(mixed $input): mixed
    {
        if (! is_string($input)) {
            return $input;
        }

        // Arabic-Indic and Eastern Arabic-Indic digits → ASCII.
        $phone = strtr($input, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        $hasPlus = str_starts_with(trim($phone), '+');
        $digits = preg_replace('/\D+/', '', $phone);

        if ($hasPlus || str_starts_with($digits, '00963') || str_starts_with($digits, '963')) {
            $digits = preg_replace('/^(00)?963/', '', $digits);
        }

        // "999111222" (9 digits, missing the trunk 0) → "0999111222".
        if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits === '' ? trim($input) : $digits;
    }
}
