<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Mirrors Api\NotificationController (database-channel notifications).
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('site.notifications', [
            'notifications' => $request->user()->notifications()->paginate(30),
        ]);
    }

    // Marks one notification read, then sends the user to whatever it's
    // about — the same payload keys the app deep-links on (see
    // App\Notifications\*::toArray()).
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        $notification?->markAsRead();

        $data = $notification?->data ?? [];

        return redirect()->to(match ($data['type'] ?? null) {
            'new_message' => isset($data['conversation_id']) ? route('site.messages.show', $data['conversation_id']) : route('site.messages'),
            'ad_approved', 'ad_rejected' => route('site.my.ads'),
            'new_rating' => route('site.profile'),
            default => route('site.notifications'),
        });
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'تم تحديد الكل كمقروء.');
    }
}
