<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');

        $users = User::when($q, fn ($query, $v) => $query->where(fn ($w) => $w
            ->where('name', 'like', "%{$v}%")
            ->orWhere('phone', 'like', "%{$v}%")
        ))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'q' => $q]);
    }

    // The rules themselves (admins can't be banned, tokens revoked) live
    // in User::ban(), shared with Api\Admin\AdminUserController@ban.
    public function ban(User $user)
    {
        if (! $user->ban()) {
            return back()->withErrors(['user' => 'لا يمكن حظر مستخدم يمتلك صلاحيات إدارية.']);
        }

        return back()->with('status', "تم حظر {$user->name}.");
    }

    public function unban(User $user)
    {
        $user->unban();

        return back()->with('status', "تم إلغاء حظر {$user->name}.");
    }
}
