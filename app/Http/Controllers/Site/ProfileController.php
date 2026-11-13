<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
        ]);

        $user->update($data);

        return back()->with('status', __('تم حفظ بياناتك.'));
    }
}
