<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = ['rated_user_id', 'rater_user_id', 'ad_id', 'score', 'comment'];

    public function ratedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    public function raterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_user_id');
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
