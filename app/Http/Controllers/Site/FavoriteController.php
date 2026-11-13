<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\Request;

// Mirrors Api\FavoriteController.
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $ads = Ad::whereHas('favoritedBy', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['images', 'city'])
            ->latest()
            ->paginate(20);

        return view('site.favorites', compact('ads'));
    }

    public function store(Request $request, Ad $ad)
    {
        // An ad that isn't approved yet is invisible to everyone but its
        // owner, so it can't be favorited either (same as the API).
        abort_unless($ad->status === 'approved', 404);

        $request->user()->favorites()->firstOrCreate(['ad_id' => $ad->id]);

        return back()->with('status', __('أُضيف إلى المفضلة.'));
    }

    public function destroy(Request $request, Ad $ad)
    {
        $request->user()->favorites()->where('ad_id', $ad->id)->delete();

        return back()->with('status', __('أُزيل من المفضلة.'));
    }
}
