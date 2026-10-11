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

    // See User::ban() for the rules (shared with the /admin-panel).
    public function ban(User $user)
    {
        abort_unless($user->ban(), 422, 'لا يمكن حظر مستخدم يمتلك صلاحيات إدارية');

        return new UserResource($user);
    }

    public function unban(User $user)
    {
        $user->unban();

        return new UserResource($user);
    }
}
