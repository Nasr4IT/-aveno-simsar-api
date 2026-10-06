<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

// See docs/HOW_IT_WORKS.md § Reporting. Lets a user flag an ad or another
// user for admin review — this never takes action by itself (no auto-ban,
// no auto-unlist); it just queues something for a human to look at via
// Admin\AdminReportController.
class ReportController extends Controller
{
    private const RULES = [
        'reason' => ['required', 'in:spam,inappropriate,scam,other'],
        'details' => ['nullable', 'string', 'max:1000'],
    ];

    // POST /api/ads/{ad}/report
    public function reportAd(Request $request, Ad $ad)
    {
        abort_unless(in_array($ad->status, ['approved', 'sold'], true), 404);
        abort_if($ad->user_id === $request->user()->id, 422, 'لا يمكنك الإبلاغ عن إعلانك الخاص');

        $data = $request->validate(self::RULES);

        $this->fileReport($request->user()->id, $ad, $data);

        return response()->json(['message' => 'تم إرسال البلاغ']);
    }

    // POST /api/users/{user}/report
    public function reportUser(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'لا يمكنك الإبلاغ عن نفسك');

        $data = $request->validate(self::RULES);

        $this->fileReport($request->user()->id, $user, $data);

        return response()->json(['message' => 'تم إرسال البلاغ']);
    }

    // One open report per (reporter, target): re-reporting the same thing
    // updates the reason/details and reopens it (status back to pending,
    // any prior admin decision cleared) rather than piling up duplicates
    // or staying silently dismissed against a fresh complaint.
    private function fileReport(int $reporterId, Ad|User $target, array $data): void
    {
        Report::updateOrCreate(
            ['reporter_id' => $reporterId, 'reportable_type' => $target::class, 'reportable_id' => $target->id],
            [...$data, 'status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null]
        );
    }
}
