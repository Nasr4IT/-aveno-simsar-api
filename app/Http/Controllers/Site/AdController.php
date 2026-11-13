<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdRequest;
use App\Models\Ad;
use App\Models\Category;
use App\Models\Governorate;
use App\Services\AdAttributeService;
use App\Services\AdFeedQueryService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdController extends Controller
{
    public function __construct(
        private AdFeedQueryService $feed,
        private AdAttributeService $attributes,
    ) {}

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

    // GET /sell — the "sell your item" page. Picking a category reloads the
    // page with that category's dynamic specs rendered (no JS framework), so
    // ?category_id= (or old() after a failed submit) drives which fields show.
    public function create(Request $request)
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $categoryId = old('category_id', $request->query('category_id'));
        $selected = $categoryId
            ? Category::with(['attributes_', 'children', 'parent'])->where('is_active', true)->find($categoryId)
            : null;

        $governorates = Governorate::where('is_active', true)
            ->with(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('id')])
            ->orderBy('id')
            ->get();

        return view('site.sell', compact('categories', 'selected', 'governorates'));
    }

    // POST /sell — identical rules and outcome to POST /api/ads: the same
    // StoreAdRequest, the same AdAttributeService checks, status=pending
    // awaiting the admin's review.
    public function store(StoreAdRequest $request, ImageService $imageService)
    {
        $data = $request->validated();

        $category = Category::with('attributes_')->findOrFail($data['category_id']);

        $errors = $this->attributes->validate($category, $data['attributes'] ?? [], enforceRequired: true);
        if ($errors) {
            throw ValidationException::withMessages(['attributes' => $errors]);
        }

        DB::transaction(function () use ($request, $data, $category, $imageService) {
            $ad = Ad::create([
                'user_id' => $request->user()->id,
                'category_id' => $category->id,
                'governorate_id' => $data['governorate_id'],
                'city_id' => $data['city_id'],
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['title']),
                'description' => $data['description'],
                'price' => $data['price'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => 'pending',
            ]);

            $this->attributes->sync($ad, $category, $data['attributes'] ?? []);
            $imageService->storeAdImages($ad, $request->file('images', []));
        });

        return redirect()->route('site.my.ads')->with('status', __('تم إرسال إعلانك للمراجعة — سيظهر للجميع فور موافقة الإدارة عليه.'));
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'ad';

        do {
            $slug = "{$base}-".Str::lower(Str::random(6));
        } while (Ad::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }
}
