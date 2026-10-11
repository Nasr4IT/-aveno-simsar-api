<?php

namespace App\Http\Requests\Admin;

use App\Models\CategoryAttribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Shared by Api\Admin\AdminCategoryController and the /admin-panel form,
// so the two clients can't drift apart on what a valid attribute is.
class StoreCategoryAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by the 'admin' route middleware
    }

    // The panel's plain HTML field sends a select's choices as one string —
    // split it on the ASCII comma or the Arabic one (U+060C, what an Arabic
    // keyboard types) into the same array the API takes directly.
    protected function prepareForValidation(): void
    {
        $options = $this->input('options');

        if (is_string($options)) {
            $this->merge(['options' => array_values(array_filter(
                array_map('trim', preg_split('/[,،]/u', $options)),
                fn ($option) => $option !== ''
            ))]);
        }
    }

    public function rules(): array
    {
        return [
            'key' => [
                'required', 'string', 'max:60',
                // The key becomes a query-param name on GET /api/ads
                // (?fuel_type=...). PHP rewrites spaces and dots in those to
                // underscores, and AdController strips its own reserved
                // params first — either way such a key could never filter.
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::notIn(CategoryAttribute::RESERVED_FILTER_KEYS),
                Rule::unique('category_attributes')->where('category_id', $this->route('category')->id),
            ],
            'label_ar' => ['required', 'string', 'max:100'],
            'label_en' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'in:text,number,boolean,select,multiselect'],
            // Only a select/multiselect has choices, and it needs at least
            // one — with none, no submitted value could ever pass
            // AdController::validateDynamicAttributes().
            'options' => ['exclude_unless:type,select,multiselect', 'required', 'array', 'min:1'],
            'options.*' => ['required', 'string', 'max:100', 'distinct'],
            // Left out entirely when not sent, so the column defaults apply
            // (is_filterable defaults to true).
            'is_required' => ['sometimes', 'boolean'],
            'is_filterable' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex' => 'المفتاح يجب أن يكون بأحرف إنجليزية صغيرة وأرقام و _ فقط، ويبدأ بحرف (مثل fuel_type).',
            'key.not_in' => 'هذا المفتاح محجوز لفلاتر البحث العامة، اختر اسمًا آخر.',
            'key.unique' => 'هذا المفتاح مستخدم مسبقًا في هذه الفئة.',
            'options.required' => 'أدخل خيارًا واحدًا على الأقل لحقل الاختيار.',
            'options.*.distinct' => 'الخيارات يجب ألا تتكرر.',
        ];
    }
}
