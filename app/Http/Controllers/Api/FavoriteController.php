<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use Illuminate\Http\Request;

// See docs/API_CONTRACT.md § Favorites ("قائمة المفضلة للمستخدم").
class FavoriteController extends Controller
{
    // GET /api/favorites
    public function index(Request $request)
    {
        $ads = Ad::whereHas('favoritedBy', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('images')
            ->paginate(20);

        return AdResource::collection($ads);
    }

    // POST /api/ads/{ad}/favorite
    public function store(Request $request, Ad $ad)
    {
        // Matches AdController@show: an ad that isn't approved yet is
        // invisible to everyone but its owner, so it can't be favorited either.
        abort_unless($ad->status === 'approved', 404);

        $request->user()->favorites()->firstOrCreate(['ad_id' => $ad->id]);

        return response()->json(['message' => 'أُضيف إلى المفضلة']);
    }

    // DELETE /api/ads/{ad}/favorite
    public function destroy(Request $request, Ad $ad)
    {
        $request->user()->favorites()->where('ad_id', $ad->id)->delete();

        return response()->json(['message' => 'أُزيل من المفضلة']);
    }
}
