<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// See docs/API_CONTRACT.md § Notifications ("إشعارات داخل التطبيق وعبر البريد الإلكتروني").
class NotificationController extends Controller
{
    // GET /api/notifications
    public function index(Request $request)
    {
        return $request->user()->notifications()->paginate(30);
    }

    // POST /api/notifications/{id}/read
    public function markRead(Request $request, string $id)
    {
        $request->user()->notifications()->where('id', $id)->first()?->markAsRead();

        return response()->json(['message' => 'تم التحديث']);
    }

    // POST /api/notifications/read-all
    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'تم التحديث']);
    }
}
