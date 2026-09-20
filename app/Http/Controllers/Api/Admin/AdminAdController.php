<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Notifications\AdApproved;
use App\Notifications\AdRejected;
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

    public function approve(Request $request, Ad $ad)
    {
        $ad->update([
            'status' => 'approved',
            'published_at' => now(),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $ad->user->notify(new AdApproved($ad));

        return new AdResource($ad);
    }

    public function reject(Request $request, Ad $ad)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $ad->update([
            'status' => 'rejected',
            'rejection_reason' => $data['reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $ad->user->notify(new AdRejected($ad));

        return new AdResource($ad);
    }
}
