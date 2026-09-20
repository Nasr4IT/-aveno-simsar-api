<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'attributes' => ['sometimes', 'array'],
        ];
    }
}
