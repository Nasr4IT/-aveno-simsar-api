<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdRequest;
use App\Http\Requests\UpdateAdRequest;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Services\AdAttributeService;
use App\Services\AdFeedQueryService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * See docs/API_CONTRACT.md § Ads. This is the heart of the MVP:
 * "نشر الإعلانات بخصائص ديناميكية" + "الفلترة والبحث الجغرافي" (proposal §3).
 *
 * The feed's filter/search/sort pipeline and the dynamic-attribute
 * validation live in App\Services (AdFeedQueryService, AdAttributeService)
 * because the website (Site\*) exposes the same features — one
 * implementation, so the two surfaces can't drift apart.
 */
class AdController extends Controller
{
    public function __construct(
        private AdFeedQueryService $feed,
        private AdAttributeService $attributes,
    ) {}

    // GET /api/ads — public feed with filters: category_id, governorate_id, city_id,
    // min_price, max_price, q (title search), sort, plus one query param per
    // filterable category_attribute (e.g. ?fuel_type=بنزين). Only status=approved
    // is returned.
    public function index(Request $request)
    {
        $filters = $request->validate(AdFeedQueryService::filterRules());

        $ads = $this->feed->build($filters, $request->query())->paginate(20)->withQueryString();

        return AdResource::collection($ads);
    }

    // GET /api/ads/{ad} — increments views_count, returns full detail incl. seller info.
    // A sold ad stays viewable (closure for anyone who had it favorited/in a
    // conversation, and the seller can point back to it) — it's only hidden
    // from the browse feed itself, via Ad::scopeApproved().
    public function show(Ad $ad)
    {
        abort_unless(in_array($ad->status, ['approved', 'sold'], true), 404);

        $ad->increment('views_count');
        $ad->load([...AdFeedQueryService::CARD_RELATIONS, 'attributeValues.attribute'])
            ->loadCount(['favoritedBy', 'conversations']);

        return new AdResource($ad);
    }

    // GET /api/ads/{ad}/similar — same category, price within ±30% when the
    // ad has one, closest price first. Small unpaginated set (like
    // /categories, /ad-packages, /banners) for a "similar ads" rail on the
    // detail page.
    public function similar(Ad $ad)
    {
        abort_unless(in_array($ad->status, ['approved', 'sold'], true), 404);

        return AdResource::collection($this->feed->similarTo($ad)->get());
    }

    // POST /api/ads/{ad}/mark-sold — owner only. Removes it from the public
    // feed (scopeApproved only matches 'approved') while keeping it
    // reachable via GET /ads/{id} for closure, same as any approved ad.
    public function markSold(Ad $ad)
    {
        $this->authorizeOwner($ad);
        abort_unless($ad->status === 'approved', 422, 'لا يمكن تحديد إعلان غير منشور كمباع');

        $ad->update(['status' => 'sold']);

        return new AdResource($ad);
    }

    // POST /api/ads/{ad}/relist — owner only. Sends a sold/expired ad back
    // through the normal review queue (resetToPending — same as any other
    // edit) rather than instantly re-publishing it, since the listing may
    // be stale (price, availability) after sitting inactive.
    public function relist(Ad $ad)
    {
        $this->authorizeOwner($ad);
        abort_unless(in_array($ad->status, ['sold', 'expired'], true), 422, 'يمكن إعادة النشر فقط للإعلانات المباعة أو منتهية الصلاحية');

        $this->resetToPending($ad);

        return new AdResource($ad);
    }

    // POST /api/ads — multipart/form-data (it carries files). Creates as
    // status=pending, awaiting admin review. See docs/API_CONTRACT.md § Ads
    // for the exact field names, including attributes[<key>] and images[].
    public function store(StoreAdRequest $request, ImageService $imageService)
    {
        $data = $request->validated();

        $category = Category::with('attributes_')->findOrFail($data['category_id']);

        $errors = $this->attributes->validate($category, $data['attributes'] ?? [], enforceRequired: true);
        if ($errors) {
            throw ValidationException::withMessages(['attributes' => $errors]);
        }

        $ad = DB::transaction(function () use ($request, $data, $category, $imageService) {
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

            return $ad;
        });

        $ad->load(['images', 'attributeValues.attribute', 'category', 'user']);

        return (new AdResource($ad))->response()->setStatusCode(201);
    }

    // PUT/PATCH /api/ads/{ad} — owner only; any edit resets status to pending
    // so an admin re-reviews it. Only touches the fields actually sent.
    public function update(UpdateAdRequest $request, Ad $ad)
    {
        $this->authorizeOwner($ad);
        $this->assertEditable($ad);

        $data = $request->validated();
        $category = $ad->category()->with('attributes_')->firstOrFail();

        if (array_key_exists('attributes', $data)) {
            $errors = $this->attributes->validate($category, $data['attributes'] ?? [], enforceRequired: false);
            if ($errors) {
                throw ValidationException::withMessages(['attributes' => $errors]);
            }
        }

        DB::transaction(function () use ($ad, $data, $category) {
            $ad->fill(array_intersect_key($data, array_flip(['title', 'description', 'price'])));
            $this->resetToPending($ad);

            if (array_key_exists('attributes', $data)) {
                $this->attributes->sync($ad, $category, $data['attributes'] ?? []);
            }
        });

        $ad->load(['images', 'attributeValues.attribute', 'category', 'user']);

        return new AdResource($ad);
    }

    // DELETE /api/ads/{ad} — owner only (soft delete).
    public function destroy(Request $request, Ad $ad)
    {
        $this->authorizeOwner($ad);

        $ad->delete();

        return response()->json(['message' => 'تم حذف الإعلان']);
    }

    // POST /api/ads/{ad}/images — owner only, multipart/form-data. Appends to
    // the existing gallery (up to 12 total, matching StoreAdRequest) rather
    // than replacing it, since a seller adding one more photo shouldn't have
    // to re-upload the rest. Like any other edit, this sends the ad back to
    // pending for re-review.
    public function addImages(Request $request, Ad $ad, ImageService $imageService)
    {
        $this->authorizeOwner($ad);
        $this->assertEditable($ad);

        $existingCount = $ad->images()->count();

        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:'.max(0, 12 - $existingCount)],
            'images.*' => ['image', 'max:5120'],
        ]);

        DB::transaction(function () use ($ad, $request, $imageService) {
            $imageService->storeAdImages($ad, $request->file('images', []));
            $this->resetToPending($ad);
        });

        $ad->load(['images', 'attributeValues.attribute', 'category', 'user']);

        return new AdResource($ad);
    }

    // DELETE /api/ads/{ad}/images/{image} — owner only. An ad must always
    // keep at least one photo (StoreAdRequest requires min:1 to create one),
    // so the last remaining image can't be removed this way — the seller
    // would delete the whole ad instead.
    public function removeImage(Request $request, Ad $ad, AdImage $image)
    {
        $this->authorizeOwner($ad);
        $this->assertEditable($ad);

        abort_unless($image->ad_id === $ad->id, 404);
        abort_if($ad->images()->count() <= 1, 422, 'يجب أن يحتوي الإعلان على صورة واحدة على الأقل — احذف الإعلان بالكامل إذا أردت إزالته.');

        DB::transaction(function () use ($ad, $image) {
            $wasCover = $image->is_cover;

            Storage::disk(config('filesystems.default'))->delete($image->path);
            $image->delete();

            if ($wasCover) {
                $ad->images()->orderBy('sort_order')->first()?->update(['is_cover' => true]);
            }

            $this->resetToPending($ad);
        });

        $ad->load(['images', 'attributeValues.attribute', 'category', 'user']);

        return new AdResource($ad);
    }

    // Any post-creation edit (fields, attributes, or photos) sends the ad
    // back to pending so an admin re-reviews it, clearing any prior
    // rejection so the listing doesn't still show stale rejection info.
    private function resetToPending(Ad $ad): void
    {
        $ad->status = 'pending';
        $ad->rejection_reason = null;
        $ad->reviewed_by = null;
        $ad->reviewed_at = null;
        $ad->save();
    }

    // GET /api/my/ads — the authenticated user's own ads, any status.
    public function myAds(Request $request)
    {
        return AdResource::collection(
            $request->user()->ads()->with(AdFeedQueryService::CARD_RELATIONS)->withCount(['favoritedBy', 'conversations'])->latest()->paginate(20)
        );
    }

    // GET /api/my/ads/stats — aggregate seller dashboard numbers across all
    // of the caller's ads. One query (withCount runs as subselects on it,
    // not N+1) since a seller's own ad count is always small enough to
    // pull into memory and sum client-side here.
    public function myAdsStats(Request $request)
    {
        $ads = $request->user()->ads()
            ->withCount(['favoritedBy', 'conversations'])
            ->get(['id', 'status', 'views_count']);

        return response()->json([
            'data' => [
                'total_ads' => $ads->count(),
                'by_status' => [
                    'pending' => $ads->where('status', 'pending')->count(),
                    'approved' => $ads->where('status', 'approved')->count(),
                    'rejected' => $ads->where('status', 'rejected')->count(),
                    'sold' => $ads->where('status', 'sold')->count(),
                    'expired' => $ads->where('status', 'expired')->count(),
                ],
                'total_views' => (int) $ads->sum('views_count'),
                // withCount snake_cases the relation name for its generated
                // attribute key regardless of how the relation method itself
                // is named — favoritedBy becomes favorited_by_count.
                'total_favorites' => (int) $ads->sum('favorited_by_count'),
                'total_conversations' => (int) $ads->sum('conversations_count'),
            ],
        ]);
    }

    private function authorizeOwner(Ad $ad): void
    {
        abort_unless($ad->user_id === request()->user()->id, 403);
    }

    // update()/addImages()/removeImage() all reset the ad to pending for
    // re-review — but a sold/expired ad is meant to be frozen until the
    // owner explicitly relist()s it (which has its own validated
    // sold/expired-only guard). Without this, editing e.g. just the title
    // of a sold ad would silently un-sell it as a side effect.
    private function assertEditable(Ad $ad): void
    {
        abort_unless(
            in_array($ad->status, ['pending', 'approved', 'rejected'], true),
            422,
            'لا يمكن تعديل إعلان مُباع أو منتهي الصلاحية — استخدم خاصية إعادة النشر أولاً.'
        );
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
