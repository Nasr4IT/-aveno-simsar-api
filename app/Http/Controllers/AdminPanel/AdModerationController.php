<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Notifications\AdApproved;
use App\Notifications\AdRejected;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class AdModerationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $ads = Ad::when($status, fn ($q, $v) => $q->where('status', $v))
            ->with(['user', 'category', 'images'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.ads.index', ['ads' => $ads, 'status' => $status]);
    }

    public function approve(Ad $ad, PushNotificationService $push)
    {
        $this->assertReviewable($ad);

        $ad->update([
            'status' => 'approved',
            'published_at' => now(),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $ad->user->notify(new AdApproved($ad));
        // The push goes to the seller's phone — stays Arabic whatever language the admin uses.
        $push->sendToUser($ad->user, 'تمت الموافقة على إعلانك', $ad->title, ['type' => 'ad_approved', 'ad_id' => $ad->id]);

        return back()->with('status', __('تمت الموافقة على ":title".', ['title' => $ad->title]));
    }

    public function reject(Request $request, Ad $ad, PushNotificationService $push)
    {
        $this->assertReviewable($ad);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $ad->update([
            'status' => 'rejected',
            'rejection_reason' => $data['reason'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $ad->user->notify(new AdRejected($ad));
        $push->sendToUser($ad->user, 'تم رفض إعلانك', $ad->title, ['type' => 'ad_rejected', 'ad_id' => $ad->id]);

        return back()->with('status', __('تم رفض ":title".', ['title' => $ad->title]));
    }

    // Sold/expired ads are outside the review workflow — see the matching
    // guard and comment in Api\Admin\AdminAdController. abort_unless (not
    // back()->withErrors()) matches this panel's existing convention for a
    // hard business-rule stop — see UserManagementController::ban().
    private function assertReviewable(Ad $ad): void
    {
        abort_unless(
            in_array($ad->status, ['pending', 'approved', 'rejected'], true),
            422,
            __('لا يمكن تغيير حالة مراجعة إعلان مُباع أو منتهي الصلاحية.')
        );
    }
}
