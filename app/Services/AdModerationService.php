<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\User;
use App\Notifications\AdApproved;
use App\Notifications\AdRejected;

// The one place ad review decisions live — Api\Admin\AdminAdController and
// the /admin-panel both go through this, so the two can't drift apart.
//
// Each decision only applies from the statuses listed, and that check is
// part of the UPDATE itself rather than a read-then-write, so a stale tab,
// a second admin, or a double-click can't apply it twice (or re-notify the
// owner). A refused decision changes nothing and returns false.
class AdModerationService
{
    public function __construct(private readonly PushNotificationService $push) {}

    // Only from pending: a sold/expired ad comes back through relist, and a
    // rejected one through an owner edit — both of which reset it to
    // pending for a fresh review first.
    public function approve(Ad $ad, User $reviewer): bool
    {
        $applied = $this->transition($ad, ['pending'], [
            'status' => 'approved',
            'published_at' => now(),
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        if ($applied) {
            $ad->user->notify(new AdApproved($ad));
            $this->push->sendToUser($ad->user, 'تمت الموافقة على إعلانك', $ad->title, ['type' => 'ad_approved', 'ad_id' => $ad->id]);
        }

        return $applied;
    }

    // From pending (the review queue) or approved (taking a live ad down,
    // e.g. after a report — docs/HOW_IT_WORKS.md § Reporting).
    public function reject(Ad $ad, User $reviewer, string $reason): bool
    {
        $applied = $this->transition($ad, ['pending', 'approved'], [
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        if ($applied) {
            $ad->user->notify(new AdRejected($ad));
            $this->push->sendToUser($ad->user, 'تم رفض إعلانك', $ad->title, ['type' => 'ad_rejected', 'ad_id' => $ad->id]);
        }

        return $applied;
    }

    private function transition(Ad $ad, array $fromStatuses, array $changes): bool
    {
        $updated = Ad::whereKey($ad->getKey())->whereIn('status', $fromStatuses)->update($changes);

        $ad->refresh();

        return $updated > 0;
    }
}
