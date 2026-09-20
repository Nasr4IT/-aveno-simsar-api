<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar_url' => $this->avatar_path ? asset('storage/'.$this->avatar_path) : null,
            'governorate' => $this->whenLoaded('governorate'),
            'city' => $this->whenLoaded('city'),
            'rating_average' => (float) $this->rating_average,
            'rating_count' => $this->rating_count,
            'is_banned' => (bool) $this->is_banned,
            'created_at' => $this->created_at,
        ];
    }
}
