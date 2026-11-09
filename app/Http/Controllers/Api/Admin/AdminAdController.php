<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Notifications\AdApproved;
use App\Notifications\AdRejected;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

// See docs/API_CONTRACT.md § Admin ▸ Ads ("نظام مراجعة المنشورات — قبول/رفض").
class AdminAdController extends Controller
{
    // GET /api/admin/ads?status=pending
    public function index(Request $request)
    {
        return AdResource::collection(
            Ad::when($request->status, fn ($q, $status) => $q->where('status', $status))
                ->with(['user', 'category', 'images'])
                ->latest()
                ->paginate(30)
        );
    }

    public function approve(Request $request, Ad $ad, PushNotificationService $push)
    {
        $this->assertReviewable($ad);

        $ad->update([
            'status' => 'approved',
            'published_at' => now(),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $ad->user->notify(new AdApproved($ad));
        $push->sendToUser($ad->user, 'تمت الموافقة على إعلانك', $ad->title, ['type' => 'ad_approved', 'ad_id' => $ad->id]);

        return new AdResource($ad);
    }

    public function reject(Request $request, Ad $ad, PushNotificationService $push)
    {
        $this->assertReviewable($ad);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $ad->update([
            'status' => 'rejected',
            'rejection_reason' => $data['reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $ad->user->notify(new AdRejected($ad));
        $push->sendToUser($ad->user, 'تم رفض إعلانك', $ad->title, ['type' => 'ad_rejected', 'ad_id' => $ad->id]);

        return new AdResource($ad);
    }

    // Sold/expired ads are outside the review workflow — the owner would
    // relist() one back to pending before it's reviewable again. Without
    // this, an admin could e.g. "reject" an ad that already sold, firing a
    // confusing "your ad was rejected" notification at its owner.
    private function assertReviewable(Ad $ad): void
    {
        abort_unless(
            in_array($ad->status, ['pending', 'approved', 'rejected'], true),
            422,
            'لا يمكن تغيير حالة مراجعة إعلان مُباع أو منتهي الصلاحية.'
        );
    }
}
