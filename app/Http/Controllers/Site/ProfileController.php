<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

// The website's "My account" page — same rules as the API's /auth/me,
// /auth/change-password and DELETE /auth/me (both go through AccountService).
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('site.profile', [
            'user' => $user,
            'ratings' => $user->ratingsReceived()->with('raterUser')->latest()->paginate(10),
            'governorates' => Governorate::where('is_active', true)
                ->with(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('id')])
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function update(Request $request, AccountService $accounts)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'avatar' => ['nullable', 'image', 'max:5120'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $accounts->updateProfile($user, $data, $request->file('avatar'), $request->boolean('remove_avatar'));

        return back()->with('status', __('تم حفظ بياناتك.'));
    }

    public function changePassword(Request $request)
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();
        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        // Sign the app out everywhere else too, like the API does.
        $user->tokens()->delete();

        return back()->with('status', __('تم تغيير كلمة المرور.'));
    }

    public function destroy(Request $request, AccountService $accounts)
    {
        $request->validateWithBag('deleteAccount', [
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $accounts->delete($user);

        return redirect()->route('site.home')->with('status', __('تم حذف حسابك.'));
    }
}
