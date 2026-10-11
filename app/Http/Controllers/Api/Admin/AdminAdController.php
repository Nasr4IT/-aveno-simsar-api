<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Services\AdModerationService;
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

    // Only a pending ad — see AdModerationService for why.
    public function approve(Request $request, Ad $ad, AdModerationService $moderation)
    {
        abort_unless($moderation->approve($ad, $request->user()), 422, 'يمكن الموافقة فقط على إعلان بانتظار المراجعة');

        return new AdResource($ad);
    }

    // A pending ad, or an approved/sold one to take it down.
    public function reject(Request $request, Ad $ad, AdModerationService $moderation)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        abort_unless($moderation->reject($ad, $request->user(), $data['reason']), 422, 'يمكن رفض الإعلانات بانتظار المراجعة أو المنشورة أو المباعة فقط');

        return new AdResource($ad);
    }
}
