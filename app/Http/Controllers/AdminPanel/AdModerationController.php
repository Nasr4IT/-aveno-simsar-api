<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Services\AdModerationService;
use Illuminate\Http\Request;

class AdModerationController extends Controller
{
    public function index(Request $request)
    {
        // "all" is an explicit value rather than a missing param: a missing
        // ?status= means the default pending queue, and withQueryString()
        // has to carry the choice onto page 2.
        $status = $request->query('status') ?: 'pending';

        $ads = Ad::when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['user', 'category', 'images'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.ads.index', ['ads' => $ads, 'status' => $status]);
    }

    public function approve(Request $request, Ad $ad, AdModerationService $moderation)
    {
        if (! $moderation->approve($ad, $request->user())) {
            return back()->withErrors(['ad' => "لم يعد \"{$ad->title}\" بانتظار المراجعة (حالته الآن: {$ad->status})."]);
        }

        return back()->with('status', "تمت الموافقة على \"{$ad->title}\".");
    }

    public function reject(Request $request, Ad $ad, AdModerationService $moderation)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        if (! $moderation->reject($ad, $request->user(), $data['reason'])) {
            return back()->withErrors(['ad' => "لا يمكن رفض \"{$ad->title}\" وهو بحالة {$ad->status}."]);
        }

        return back()->with('status', "تم رفض \"{$ad->title}\".");
    }
}
