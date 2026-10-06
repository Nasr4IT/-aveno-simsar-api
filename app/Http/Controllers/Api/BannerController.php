<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;

// See docs/HOW_IT_WORKS.md § Sponsored Banners. Public, unauthenticated —
// this feeds the home-screen carousel, separate from the ads feed.
class BannerController extends Controller
{
    // GET /api/banners — only banners currently within their admin-set
    // window and not paused. Small, unpaginated set, like /categories and
    // /ad-packages.
    public function index()
    {
        return BannerResource::collection(
            Banner::active()->orderBy('sort_order')->orderBy('created_at')->get()
        );
    }
}
