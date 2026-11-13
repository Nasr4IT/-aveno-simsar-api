<?php

namespace App\Http\Requests;

use App\Models\Ad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by the 'auth:sanctum' route middleware
    }

    public function rules(): array
    {
        return [
            // Scoped to is_active categories only — an admin deactivating a
            // category (CategoryManagementController/AdminCategoryController)
            // is meant to stop new ads from being filed into it, not just
            // hide it from browsing.
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'images' => ['required', 'array', 'min:1', 'max:12'],
            'images.*' => ['image', 'max:5120'],
            // Dynamic per-category fields arrive as attributes[<key>] = value; validated
            // at runtime against category_attributes.is_required/type (see AdController TODO).
            'attributes' => ['nullable', 'array'],
            ...Ad::optionRules(),
        ];
    }
}
