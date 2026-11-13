<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Services\AdFeedQueryService;
use Illuminate\Http\Request;

// See docs/API_CONTRACT.md § Favorites ("قائمة المفضلة للمستخدم").
class FavoriteController extends Controller
{
    // GET /api/favorites
    public function index(Request $request)
    {
        // Only ads the user can still open: one that went back to pending or
        // was rejected after being favorited 404s on GET /ads/{id}.
        $ads = Ad::whereHas('favoritedBy', fn ($q) => $q->where('user_id', $request->user()->id))
            ->whereIn('status', ['approved', 'sold'])
            ->with(AdFeedQueryService::CARD_RELATIONS)
            ->latest()
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
