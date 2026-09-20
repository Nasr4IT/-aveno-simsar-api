<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Notifications\Notification;

class AdRejected extends Notification
{
    public function __construct(private readonly Ad $ad) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ad_rejected',
            'ad_id' => $this->ad->id,
            'title' => $this->ad->title,
            'reason' => $this->ad->rejection_reason,
            'message' => "تم رفض إعلانك \"{$this->ad->title}\": {$this->ad->rejection_reason}",
        ];
    }
}
