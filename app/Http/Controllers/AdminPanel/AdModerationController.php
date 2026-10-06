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
        $ad->update([
            'status' => 'approved',
            'published_at' => now(),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $ad->user->notify(new AdApproved($ad));
        $push->sendToUser($ad->user, 'تمت الموافقة على إعلانك', $ad->title, ['type' => 'ad_approved', 'ad_id' => $ad->id]);

        return back()->with('status', "تمت الموافقة على \"{$ad->title}\".");
    }

    public function reject(Request $request, Ad $ad, PushNotificationService $push)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $ad->update([
            'status' => 'rejected',
            'rejection_reason' => $data['reason'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $ad->user->notify(new AdRejected($ad));
        $push->sendToUser($ad->user, 'تم رفض إعلانك', $ad->title, ['type' => 'ad_rejected', 'ad_id' => $ad->id]);

        return back()->with('status', "تم رفض \"{$ad->title}\".");
    }
}
