<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image_url' => $this->image_url,
            'link_url' => $this->link_url,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
