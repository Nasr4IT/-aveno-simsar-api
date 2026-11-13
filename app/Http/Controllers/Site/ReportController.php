<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

// Mirrors Api\ReportController: flags an ad or a user for the admin's
// review queue (the admin panel's "البلاغات" page). Never acts on its own.
class ReportController extends Controller
{
    private const RULES = [
        'reason' => ['required', 'in:spam,inappropriate,scam,other'],
        'details' => ['nullable', 'string', 'max:1000'],
    ];

    public function ad(Request $request, Ad $ad)
    {
        abort_unless(in_array($ad->status, ['approved', 'sold'], true), 404);

        if ($ad->user_id === $request->user()->id) {
            return back()->withErrors(['reason' => __('لا يمكنك الإبلاغ عن إعلانك الخاص.')]);
        }

        $this->fileReport($request->user()->id, $ad, $request->validate(self::RULES));

        return back()->with('status', __('تم إرسال البلاغ — شكرًا لمساعدتك في إبقاء المنصة آمنة.'));
    }

    public function user(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['reason' => __('لا يمكنك الإبلاغ عن نفسك.')]);
        }

        $this->fileReport($request->user()->id, $user, $request->validate(self::RULES));

        return back()->with('status', __('تم إرسال البلاغ — شكرًا لمساعدتك في إبقاء المنصة آمنة.'));
    }

    // One open report per (reporter, target); re-reporting reopens it.
    private function fileReport(int $reporterId, Ad|User $target, array $data): void
    {
        Report::updateOrCreate(
            ['reporter_id' => $reporterId, 'reportable_type' => $target::class, 'reportable_id' => $target->id],
            [...$data, 'status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null]
        );
    }
}
