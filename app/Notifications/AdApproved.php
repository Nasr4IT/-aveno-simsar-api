<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Notifications\Notification;

class AdApproved extends Notification
{
    public function __construct(private readonly Ad $ad) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ad_approved',
            'ad_id' => $this->ad->id,
            'title' => $this->ad->title,
            'message' => "تمت الموافقة على إعلانك \"{$this->ad->title}\" وهو الآن منشور.",
        ];
    }
}
