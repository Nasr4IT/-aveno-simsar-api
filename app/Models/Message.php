<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        // withTrashed: a deleted account's messages stay in the other
        // person's chat, shown as "مستخدم محذوف" (see AccountService::delete).
        return $this->belongsTo(User::class, 'sender_id')->withTrashed();
    }
}
