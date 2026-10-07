<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportManagementController extends Controller
{
    public function index(Request $request)
    {
        // "all" is explicit for the same reason as AdModerationController@index.
        $status = $request->query('status') ?: 'pending';

        $reports = Report::when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['reporter', 'reportable'])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.reports.index', ['reports' => $reports, 'status' => $status]);
    }

    // Resolving/dismissing is purely a triage marker here too — same as
    // Api\Admin\AdminReportController, it never bans a user or unlists an
    // ad by itself; that's a separate, deliberate action from this same
    // panel (Users/Ads tabs).
    public function resolve(Report $report)
    {
        $report->update(['status' => 'resolved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        return back()->with('status', 'تم وضع علامة "تمت المعالجة".');
    }

    public function dismiss(Report $report)
    {
        $report->update(['status' => 'dismissed', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        return back()->with('status', 'تم تجاهل البلاغ.');
    }
}
