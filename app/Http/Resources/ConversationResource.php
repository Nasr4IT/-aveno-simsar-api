<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ad' => new AdResource($this->whenLoaded('ad')),
            'buyer' => new UserResource($this->whenLoaded('buyer')),
            'seller' => new UserResource($this->whenLoaded('seller')),
            'last_message_at' => $this->last_message_at,
            // GET /conversations only: the preview line and the unread dot.
            'last_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage ? [
                'id' => $this->latestMessage->id,
                'body' => $this->latestMessage->body,
                'sender_id' => $this->latestMessage->sender_id,
                'created_at' => $this->latestMessage->created_at,
            ] : null),
            'unread_count' => $this->whenHas('unread_count', fn () => (int) $this->unread_count),
        ];
    }
}
