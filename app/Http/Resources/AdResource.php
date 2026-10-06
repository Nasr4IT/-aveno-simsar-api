<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->when($request->routeIs('*.show'), $this->description),
            'price' => $this->price !== null ? (float) $this->price : null,
            'currency' => $this->currency,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'views_count' => $this->views_count,
            // Only present when the controller eager-loaded them via
            // withCount/loadCount (GET /ads/{id}, /my/ads, /my/ads/stats) —
            // omitted elsewhere rather than silently returning 0, so a
            // missing value in the response can't be misread as "zero".
            'favorites_count' => $this->whenCounted('favoritedBy'),
            'conversations_count' => $this->whenCounted('conversations'),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'governorate' => $this->whenLoaded('governorate'),
            'city' => $this->whenLoaded('city'),
            'user' => new UserResource($this->whenLoaded('user')),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($i) => [
                'id' => $i->id, 'url' => $i->url, 'is_cover' => $i->is_cover,
            ])),
            'attributes' => $this->whenLoaded('attributeValues', fn () => $this->attributeValues->map(fn ($v) => [
                'key' => $v->attribute->key,
                'label_ar' => $v->attribute->label_ar,
                'value' => $v->value,
            ])),
            'created_at' => $this->created_at,
            'published_at' => $this->published_at,
        ];
    }
}
