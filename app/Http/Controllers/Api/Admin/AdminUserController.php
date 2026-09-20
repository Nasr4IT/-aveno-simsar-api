<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

// See docs/API_CONTRACT.md § Admin ▸ Users.
class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        return UserResource::collection(
            User::when($request->q, fn ($q) => $q->where('name', 'like', "%{$request->q}%")
                ->orWhere('phone', 'like', "%{$request->q}%"))
                ->latest()
                ->paginate(30)
        );
    }

    // is_banned is deliberately excluded from User::$fillable (it must only ever
    // be set by this admin action, never via mass assignment elsewhere), so it's
    // set with forceFill() here rather than update().
    public function ban(User $user)
    {
        abort_if($user->hasRole('admin'), 422, 'لا يمكن حظر مستخدم يمتلك صلاحيات إدارية');

        $user->forceFill(['is_banned' => true])->save();

        // Blocking future logins isn't enough on its own — without this, a
        // banned user who's already logged in keeps full access on their
        // existing token indefinitely. Revoking every token here forces
        // them to re-authenticate immediately, which the login check then
        // correctly rejects.
        $user->tokens()->delete();

        return new UserResource($user);
    }

    public function unban(User $user)
    {
        $user->forceFill(['is_banned' => false])->save();

        return new UserResource($user);
    }
}
