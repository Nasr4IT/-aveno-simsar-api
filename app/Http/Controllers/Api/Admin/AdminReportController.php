<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\Request;

// See docs/HOW_IT_WORKS.md § Reporting. Resolving/dismissing a report is
// purely a triage marker — it doesn't itself ban a user or unlist an ad;
// the admin takes any follow-up action via the existing, separate
// AdminUserController@ban / AdminAdController endpoints.
class AdminReportController extends Controller
{
    // GET /api/admin/reports?status=pending
    public function index(Request $request)
    {
        return ReportResource::collection(
            Report::when($request->status, fn ($q, $status) => $q->where('status', $status))
                ->with(['reporter', 'reportable'])
                ->latest()
                ->paginate(30)
        );
    }

    public function resolve(Request $request, Report $report)
    {
        $report->update(['status' => 'resolved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

        return new ReportResource($report);
    }

    public function dismiss(Request $request, Report $report)
    {
        $report->update(['status' => 'dismissed', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

        return new ReportResource($report);
    }
}
