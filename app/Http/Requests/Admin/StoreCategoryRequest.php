<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

// Shared by Api\Admin\AdminCategoryController and the /admin-panel form.
class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by the 'admin' route middleware
    }

    // An empty or missing sort order means "no preference" — not NULL,
    // which the NOT NULL column would refuse.
    protected function prepareForValidation(): void
    {
        if ($this->input('sort_order') === null) {
            $this->merge(['sort_order' => 0]);
        }
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
