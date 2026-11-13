<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Contact details are private. They're returned only to the account
        // owner and to admins — never on someone else's profile, an ad's
        // seller, a rater or the other side of a chat. (A seller's phone is
        // shared on an ad only when the seller allows calls: see
        // AdResource 'contact_phone'.) Public routes have no auth middleware,
        // so the viewer is read from the Sanctum guard directly.
        $viewer = $request->user() ?? auth('sanctum')->user();
        // getRoleNames() rather than hasRole(): hasRole throws if the 'admin'
        // role row hasn't been seeded yet.
        $private = $viewer && ($viewer->id === $this->id || $viewer->getRoleNames()->contains('admin'));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->when($private, $this->phone),
            'email' => $this->when($private, $this->email),
            'avatar_url' => MediaUrl::for($this->avatar_path),
            'governorate' => $this->whenLoaded('governorate'),
            'city' => $this->whenLoaded('city'),
            'rating_average' => (float) $this->rating_average,
            'rating_count' => (int) $this->rating_count,
            'is_banned' => (bool) $this->is_banned,
            // Only on GET /users/{id} (public profile).
            'active_ads_count' => $this->whenHas('active_ads_count', fn () => (int) $this->active_ads_count),
            'sold_ads_count' => $this->whenHas('sold_ads_count', fn () => (int) $this->sold_ads_count),
            'created_at' => $this->created_at,
        ];
    }
}
