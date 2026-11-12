<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Services\AdFeedQueryService;

class AdController extends Controller
{
    public function __construct(private AdFeedQueryService $feed) {}

    // Public ad detail. Same visibility rule as Api\AdController@show: a
    // sold ad stays viewable (closure for anyone who had it favorited / in a
    // conversation) — it's only hidden from the browse feed.
    public function show(Ad $ad)
    {
        abort_unless(in_array($ad->status, ['approved', 'sold'], true), 404);

        $ad->increment('views_count');
        $ad->load(['images', 'attributeValues.attribute', 'user', 'category', 'city', 'governorate'])
            ->loadCount('favoritedBy');

        $isFavorited = auth()->check() && $ad->favoritedBy()->where('user_id', auth()->id())->exists();

        $similar = $this->feed->similarTo($ad)->get();

        return view('site.ads.show', compact('ad', 'isFavorited', 'similar'));
    }
}
