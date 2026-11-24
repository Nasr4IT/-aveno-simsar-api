<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

// The admin rejected a manual Sham Cash payment (e.g. the receipt didn't match).
class FeatureRequestRejected extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(private readonly Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'feature_rejected',
            'ad_id' => $this->payment->ad_id,
            'payment_id' => $this->payment->id,
            'title' => $this->payment->ad->title,
            'reason' => $this->payment->rejection_reason,
            'message' => "تم رفض طلب تمييز إعلانك \"{$this->payment->ad->title}\": {$this->payment->rejection_reason}",
        ];
    }
}
