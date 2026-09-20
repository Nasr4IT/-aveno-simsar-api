<?php

namespace App\Notifications;

use App\Models\Rating;
use Illuminate\Notifications\Notification;

class NewRatingReceived extends Notification
{
    public function __construct(private readonly Rating $rating) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_rating',
            'rating_id' => $this->rating->id,
            'rater_id' => $this->rating->rater_user_id,
            'rater_name' => $this->rating->raterUser->name,
            'score' => $this->rating->score,
            'message' => "قيّمك {$this->rating->raterUser->name} بـ {$this->rating->score} نجوم",
        ];
    }
}
