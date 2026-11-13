<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAdRequest;
use App\Models\Ad;
use App\Models\AdImage;
use App\Services\AdAttributeService;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

// The seller's own ads. Mirrors the owner-only half of Api\AdController
// (update / addImages / removeImage / markSold / relist / destroy / myAds /
// myAdsStats) with the same guards: owner only, and a sold/expired ad is
// frozen until explicitly relisted. Rule violations redirect back with a
// message rather than aborting, since these are plain browser forms.
class MyAdsController extends Controller
{
    public function __construct(private AdAttributeService $attributes) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $ads = $user->ads()->with('images')->withCount(['favoritedBy', 'conversations'])->latest()->paginate(20);

        $all = $user->ads()->withCount(['favoritedBy', 'conversations'])->get(['id', 'status', 'views_count']);
        $stats = [
            'total' => $all->count(),
            'approved' => $all->where('status', 'approved')->count(),
            'pending' => $all->where('status', 'pending')->count(),
            'views' => (int) $all->sum('views_count'),
            'favorites' => (int) $all->sum('favorited_by_count'),
            'conversations' => (int) $all->sum('conversations_count'),
        ];

        return view('site.my.ads', compact('ads', 'stats'));
    }

    public function edit(Request $request, Ad $ad)
    {
        $this->authorizeOwner($request, $ad);
        if ($redirect = $this->guardEditable($ad)) {
            return $redirect;
        }

        $ad->load(['images', 'category.attributes_', 'attributeValues']);

        // Current values keyed by attribute key, multiselects decoded back to
        // arrays, for pre-filling the same attribute-field partial /sell uses.
        $values = $ad->attributeValues->mapWithKeys(function ($row) use ($ad) {
            $attr = $ad->category->attributes_->firstWhere('id', $row->category_attribute_id);
            $decoded = json_decode($row->value, true);

            return $attr ? [$attr->key => is_array($decoded) ? $decoded : $row->value] : [];
        });

        return view('site.my.edit', compact('ad', 'values'));
    }

    public function update(UpdateAdRequest $request, Ad $ad)
    {
        $this->authorizeOwner($request, $ad);
        if ($redirect = $this->guardEditable($ad)) {
            return $redirect;
        }

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

        return redirect()->route('site.my.ads')->with('status', 'تم حفظ التعديلات — سيُعاد نشر الإعلان بعد مراجعته.');
    }

    public function markSold(Request $request, Ad $ad)
    {
        $this->authorizeOwner($request, $ad);
        if ($ad->status !== 'approved') {
            return back()->withErrors(['ad' => 'لا يمكن تحديد إعلان غير منشور كمباع.']);
        }

        $ad->update(['status' => 'sold']);

        return back()->with('status', "تم تحديد \"{$ad->title}\" كمباع.");
    }

    public function relist(Request $request, Ad $ad)
    {
        $this->authorizeOwner($request, $ad);
        if (! in_array($ad->status, ['sold', 'expired'], true)) {
            return back()->withErrors(['ad' => 'يمكن إعادة النشر فقط للإعلانات المباعة أو منتهية الصلاحية.']);
        }

        $this->resetToPending($ad);

        return back()->with('status', "أُعيد إرسال \"{$ad->title}\" للمراجعة.");
    }

    public function destroy(Request $request, Ad $ad)
    {
        $this->authorizeOwner($request, $ad);

        $ad->delete();

        return redirect()->route('site.my.ads')->with('status', 'تم حذف الإعلان.');
    }

    public function addImages(Request $request, Ad $ad, ImageService $imageService)
    {
        $this->authorizeOwner($request, $ad);
        if ($redirect = $this->guardEditable($ad)) {
            return $redirect;
        }

        $existingCount = $ad->images()->count();

        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:'.max(0, 12 - $existingCount)],
            'images.*' => ['image', 'max:5120'],
        ]);

        DB::transaction(function () use ($ad, $request, $imageService) {
            $imageService->storeAdImages($ad, $request->file('images', []));
            $this->resetToPending($ad);
        });

        return back()->with('status', 'تمت إضافة الصور — سيُعاد نشر الإعلان بعد مراجعته.');
    }

    public function removeImage(Request $request, Ad $ad, AdImage $image)
    {
        $this->authorizeOwner($request, $ad);
        if ($redirect = $this->guardEditable($ad)) {
            return $redirect;
        }

        abort_unless($image->ad_id === $ad->id, 404);

        if ($ad->images()->count() <= 1) {
            return back()->withErrors(['images' => 'يجب أن يحتوي الإعلان على صورة واحدة على الأقل — احذف الإعلان بالكامل إذا أردت إزالته.']);
        }

        DB::transaction(function () use ($ad, $image) {
            $wasCover = $image->is_cover;

            Storage::disk(config('filesystems.default'))->delete($image->path);
            $image->delete();

            if ($wasCover) {
                $ad->images()->orderBy('sort_order')->first()?->update(['is_cover' => true]);
            }

            $this->resetToPending($ad);
        });

        return back()->with('status', 'تم حذف الصورة.');
    }

    private function authorizeOwner(Request $request, Ad $ad): void
    {
        abort_unless($ad->user_id === $request->user()->id, 403);
    }

    // Same rule as Api\AdController::assertEditable — a sold/expired ad is
    // frozen until relist() — as a redirect instead of a 422 page.
    private function guardEditable(Ad $ad): ?RedirectResponse
    {
        if (in_array($ad->status, ['pending', 'approved', 'rejected'], true)) {
            return null;
        }

        return redirect()->route('site.my.ads')
            ->withErrors(['ad' => 'لا يمكن تعديل إعلان مُباع أو منتهي الصلاحية — استخدم "إعادة النشر" أولاً.']);
    }

    // Any post-creation edit sends the ad back through review, clearing a
    // prior rejection so stale rejection info isn't shown.
    private function resetToPending(Ad $ad): void
    {
        $ad->status = 'pending';
        $ad->rejection_reason = null;
        $ad->reviewed_by = null;
        $ad->reviewed_at = null;
        $ad->save();
    }
}
