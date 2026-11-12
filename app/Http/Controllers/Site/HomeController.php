<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Banner;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        // Same category and banner queries as Api\CategoryController@index /
        // Api\BannerController@index — the home screen of the app and of the
        // website show the same things.
        $categories = Category::with('children')
            ->withCount(['ads' => fn ($q) => $q->where('status', 'approved')])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $banners = Banner::active()->orderBy('sort_order')->orderBy('created_at')->get();

        $featured = Ad::approved()->featured()
            ->with(['images', 'city'])
            ->latest()
            ->limit(8)
            ->get();

        $latest = Ad::approved()
            ->with(['images', 'city'])
            ->latest('is_featured')->latest()
            ->limit(12)
            ->get();

        return view('site.home', compact('categories', 'banners', 'featured', 'latest'));
    }
}
