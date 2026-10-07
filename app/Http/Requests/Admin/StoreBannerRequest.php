<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

// Shared by Api\Admin\AdminBannerController and the /admin-panel form.
class StoreBannerRequest extends FormRequest
{
    // https:// or tel: only — see docs/HOW_IT_WORKS.md § Sponsored Banners.
    public const LINK_URL_RULE = 'regex:/^(https?:\/\/|tel:).+/';

    public function authorize(): bool
    {
        return true; // gated by the 'admin' route middleware
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:5120'],
            'title' => ['nullable', 'string', 'max:150'],
            'link_url' => ['nullable', 'string', 'max:500', self::LINK_URL_RULE],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
