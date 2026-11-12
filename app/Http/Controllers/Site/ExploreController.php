<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\Governorate;
use App\Services\AdFeedQueryService;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    public function __construct(private AdFeedQueryService $feed) {}

    // The browse/search page. Validation rules, filtering, search (incl. the
    // typo-tolerant fallback), sorting and per-category attribute filters
    // are all AdFeedQueryService — the exact code behind GET /api/ads, so a
    // search here returns what the app returns.
    public function index(Request $request)
    {
        $filters = $request->validate(AdFeedQueryService::filterRules());

        $ads = $this->feed->build($filters, $request->query())->paginate(20)->withQueryString();

        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $governorates = Governorate::where('is_active', true)
            ->with(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('id')])
            ->orderBy('id')
            ->get();

        // Filterable specs of the chosen category, rendered as extra filter
        // controls whose names are the attribute keys the feed service reads
        // from the query string (?fuel_type=بنزين).
        $attributeFilters = ($filters['category_id'] ?? null)
            ? CategoryAttribute::where('category_id', $filters['category_id'])->where('is_filterable', true)->get()
            : collect();

        return view('site.explore', compact('ads', 'filters', 'categories', 'governorates', 'attributeFilters'));
    }
}
