<?php

namespace App\Notifications;

use App\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

// Queued (database-channel writes, and any future mail/push channel, move
// off the request-response cycle onto QUEUE_CONNECTION=database — see
// docs/HOW_IT_WORKS.md § Queue Workers for how that's actually run).
class AdApproved extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

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
