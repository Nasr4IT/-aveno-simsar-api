<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-editable site settings (admin panel ▸ Settings), e.g. the Sham Cash
 * account buyers pay to and the contact details in the privacy policy.
 * Read through Setting::get() — cached, cleared on every write.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    // Every key the admin can edit, with its label (Arabic = translation key).
    public const KEYS = [
        'shamcash_account_name' => 'اسم صاحب حساب شام كاش',
        'shamcash_account_number' => 'رقم حساب / محفظة شام كاش',
        'shamcash_instructions' => 'تعليمات الدفع (تظهر للبائع)',
        'contact_email' => 'بريد التواصل (يظهر في سياسة الخصوصية)',
        'contact_phone' => 'هاتف التواصل (يظهر في سياسة الخصوصية)',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings', fn () => static::query()->pluck('value', 'key')->all());

        return filled($all[$key] ?? null) ? $all[$key] : $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::KEYS)) {
                static::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        Cache::forget('settings');
    }

    /** What the app / payment page needs to tell a seller where to pay. */
    public static function shamCash(): array
    {
        return [
            'account_name' => static::get('shamcash_account_name'),
            'account_number' => static::get('shamcash_account_number'),
            'instructions' => static::get('shamcash_instructions'),
        ];
    }
}
