<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryAttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label_ar' => $this->label_ar,
            'label_en' => $this->label_en,
            'type' => $this->type,
            'options' => $this->options,
            'is_required' => $this->is_required,
        ];
    }
}
