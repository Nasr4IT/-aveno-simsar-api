<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

// See docs/HOW_IT_WORKS.md § Push Notifications. The Flutter app calls
// store() once it has an FCM token (on launch / after login) and destroy()
// on logout, so a signed-out device stops receiving another user's pushes.
class DeviceTokenController extends Controller
{
    // POST /api/device-tokens
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'in:android,ios,web'],
        ]);

        // Keyed by token, not (user, token): the same physical device can
        // be re-registered under a different user after a logout/login,
        // and should then belong to whoever is logged in now, not pile up
        // a stale row for the previous account.
        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            ['user_id' => $request->user()->id, 'platform' => $data['platform'] ?? null]
        );

        return response()->json(['message' => 'تم التسجيل']);
    }

    // DELETE /api/device-tokens
    public function destroy(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string']]);

        $request->user()->deviceTokens()->where('token', $data['token'])->delete();

        return response()->json(['message' => 'تم الحذف']);
    }
}
