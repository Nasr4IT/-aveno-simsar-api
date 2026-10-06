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

    // Same rule as Api\Admin\AdminUserController@ban: admins can't ban
    // other admins, and banning revokes every existing token immediately.
    public function ban(User $user)
    {
        abort_if($user->hasRole('admin'), 422, 'لا يمكن حظر مستخدم يمتلك صلاحيات إدارية');

        $user->forceFill(['is_banned' => true])->save();
        $user->tokens()->delete();

        return back()->with('status', "تم حظر {$user->name}.");
    }

    public function unban(User $user)
    {
        $user->forceFill(['is_banned' => false])->save();

        return back()->with('status', "تم إلغاء حظر {$user->name}.");
    }
}
