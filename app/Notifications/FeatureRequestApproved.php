<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

// The admin approved a manual Sham Cash payment: the ad is now featured.
class FeatureRequestApproved extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(private readonly Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $ad = $this->payment->ad;

        return [
            'type' => 'feature_approved',
            'ad_id' => $ad->id,
            'payment_id' => $this->payment->id,
            'title' => $ad->title,
            'featured_until' => $ad->featured_until?->toIso8601String(),
            'message' => "تم تأكيد الدفع وتمييز إعلانك \"{$ad->title}\" حتى ".$ad->featured_until?->format('Y-m-d').'.',
        ];
    }
}
