<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['ad_id', 'buyer_id', 'seller_id', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function ad(): BelongsTo
    {
        // withTrashed (here and on buyer/seller): the chat history survives
        // the ad being deleted or either side deleting their account.
        return $this->belongsTo(Ad::class)->withTrashed();
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id')->withTrashed();
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id')->withTrashed();
    }

    public function messages(): HasMany
    {
        // id as tie-breaker: two messages in the same second keep send order.
        return $this->hasMany(Message::class)->orderBy('created_at')->orderBy('id');
    }

    // The newest message — the preview line in the chat list.
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany('id');
    }

    // The participant who isn't $userId.
    public function otherParticipant(int $userId): ?User
    {
        return $this->buyer_id === $userId ? $this->seller : $this->buyer;
    }
}
