<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Banner;
use App\Models\Report;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'pendingAds' => Ad::where('status', 'pending')->count(),
            'totalUsers' => User::count(),
            'bannedUsers' => User::where('is_banned', true)->count(),
            'pendingReports' => Report::where('status', 'pending')->count(),
            'activeBanners' => Banner::active()->count(),
        ]);
    }
}
