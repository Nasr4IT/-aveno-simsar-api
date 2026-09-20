<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdRequest;
use App\Http\Requests\UpdateAdRequest;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * See docs/API_CONTRACT.md § Ads. This is the heart of the MVP:
 * "نشر الإعلانات بخصائص ديناميكية" + "الفلترة والبحث الجغرافي" (proposal §3).
 */
class AdController extends Controller
{
    // GET /api/ads — public feed with filters: category_id, governorate_id, city_id,
    // min_price, max_price, q (title search), plus one query param per filterable
    // category_attribute (e.g. ?fuel_type=بنزين). Only status=approved is returned.
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'governorate_id' => ['nullable', 'integer', 'exists:governorates,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $ads = Ad::approved()
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['governorate_id'] ?? null, fn ($q, $v) => $q->where('governorate_id', $v))
            ->when($filters['city_id'] ?? null, fn ($q, $v) => $q->where('city_id', $v))
            ->when($filters['min_price'] ?? null, fn ($q, $v) => $q->where('price', '>=', $v))
            ->when($filters['max_price'] ?? null, fn ($q, $v) => $q->where('price', '<=', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($q2) => $q2
                ->where('title', 'like', "%{$v}%")
                ->orWhere('description', 'like', "%{$v}%")
            ))
            ->tap(fn ($q) => $this->applyAttributeFilters($q, $request, $filters['category_id'] ?? null))
            ->with(['images', 'category', 'city', 'governorate'])
            ->latest('is_featured')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return AdResource::collection($ads);
    }

    // Applies one whereHas(attributeValues) constraint per query param whose
    // name matches a filterable category_attribute key (e.g. ?fuel_type=بنزين,
    // per docs/API_CONTRACT.md § Ads). Scoped to $categoryId when given, since
    // the same key string can be reused with different meanings across
    // categories; without a category_id all filterable keys are eligible.
    private function applyAttributeFilters($query, Request $request, ?int $categoryId): void
    {
        $reserved = ['category_id', 'governorate_id', 'city_id', 'min_price', 'max_price', 'q', 'page'];

        $params = collect($request->query())->except($reserved)->filter(fn ($v) => $v !== null && $v !== '');
        if ($params->isEmpty()) {
            return;
        }

        $attributes = CategoryAttribute::where('is_filterable', true)
            ->whereIn('key', $params->keys())
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->get()
            ->groupBy('key');

        // Grouped by key rather than filtered one attribute row at a time:
        // the same key (e.g. "condition") can be defined on multiple
        // categories with different category_attribute_id values, so without
        // a category_id each key may match several rows. Those must be OR'd
        // within one whereHas — a separate whereHas per row would AND them
        // together, which no single ad (belonging to exactly one category)
        // could ever satisfy.
        foreach ($attributes as $key => $attrsForKey) {
            $value = $params[$key];
            $type = $attrsForKey->first()->type;
            $attributeIds = $attrsForKey->pluck('id');

            $query->whereHas('attributeValues', function ($q) use ($attributeIds, $type, $value) {
                $q->whereIn('category_attribute_id', $attributeIds);

                if ($type === 'multiselect') {
                    $q->where('value', 'like', '%"'.$value.'"%');
                } else {
                    $q->where('value', $value);
                }
            });
        }
    }

    // GET /api/ads/{ad} — increments views_count, returns full detail incl. seller info.
    public function show(Ad $ad)
    {
        abort_unless($ad->status === 'approved', 404);

        $ad->increment('views_count');
        $ad->load(['images', 'attributeValues.attribute', 'user', 'category']);

        return new AdResource($ad);
    }

    // POST /api/ads — multipart/form-data (it carries files). Creates as
    // status=pending, awaiting admin review. See docs/API_CONTRACT.md § Ads
    // for the exact field names, including attributes[<key>] and images[].
    public function store(StoreAdRequest $request, ImageService $imageService)
    {
        $data = $request->validated();

        $category = Category::with('attributes_')->findOrFail($data['category_id']);

        $errors = $this->validateDynamicAttributes($category, $data['attributes'] ?? [], enforceRequired: true);
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

            $this->syncAttributes($ad, $category, $data['attributes'] ?? []);
            $this->storeImages($ad, $request->file('images', []), $imageService);

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

        $data = $request->validated();
        $category = $ad->category()->with('attributes_')->firstOrFail();

        if (array_key_exists('attributes', $data)) {
            $errors = $this->validateDynamicAttributes($category, $data['attributes'] ?? [], enforceRequired: false);
            if ($errors) {
                throw ValidationException::withMessages(['attributes' => $errors]);
            }
        }

        DB::transaction(function () use ($ad, $data, $category) {
            $ad->fill(array_intersect_key($data, array_flip(['title', 'description', 'price'])));
            $this->resetToPending($ad);

            if (array_key_exists('attributes', $data)) {
                $this->syncAttributes($ad, $category, $data['attributes'] ?? []);
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

        $existingCount = $ad->images()->count();

        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:'.max(0, 12 - $existingCount)],
            'images.*' => ['image', 'max:5120'],
        ]);

        DB::transaction(function () use ($ad, $request, $imageService) {
            $this->storeImages($ad, $request->file('images', []), $imageService);
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

        abort_unless($image->ad_id === $ad->id, 404);
        abort_if($ad->images()->count() <= 1, 422, 'يجب أن يحتوي الإعلان على صورة واحدة على الأقل — احذف الإعلان بالكامل إذا أردت إزالته.');

        DB::transaction(function () use ($ad, $image) {
            $wasCover = $image->is_cover;

            Storage::disk('public')->delete($image->path);
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
            $request->user()->ads()->with('images')->latest()->paginate(20)
        );
    }

    private function authorizeOwner(Ad $ad): void
    {
        abort_unless($ad->user_id === request()->user()->id, 403);
    }

    // Checks required-ness, and value types/options, for a category's dynamic
    // specs (category_attributes) against the submitted attributes[] payload.
    // enforceRequired=false is used on update, where the request is a partial
    // edit and not every spec has to be resent.
    private function validateDynamicAttributes(Category $category, array $attributes, bool $enforceRequired): array
    {
        $errors = [];

        foreach ($category->attributes_ as $attr) {
            $present = array_key_exists($attr->key, $attributes)
                && $attributes[$attr->key] !== null
                && $attributes[$attr->key] !== '';

            if (! $present) {
                if ($enforceRequired && $attr->is_required) {
                    $errors[] = "الحقل \"{$attr->label_ar}\" مطلوب.";
                }

                continue;
            }

            $value = $attributes[$attr->key];

            match ($attr->type) {
                'number' => is_numeric($value) ? null : $errors[] = "قيمة \"{$attr->label_ar}\" يجب أن تكون رقمًا.",
                'select' => in_array($value, $attr->options ?? [], true) ? null : $errors[] = "قيمة \"{$attr->label_ar}\" غير صالحة.",
                'multiselect' => array_diff(is_array($value) ? $value : [$value], $attr->options ?? []) === []
                    ? null : $errors[] = "قيمة \"{$attr->label_ar}\" غير صالحة.",
                default => null,
            };
        }

        return $errors;
    }

    // Creates/updates/clears ad_attribute_values rows for exactly the keys
    // present in $attributes (unknown keys not defined on the category are
    // ignored; an empty value clears an existing row).
    private function syncAttributes(Ad $ad, Category $category, array $attributes): void
    {
        foreach ($category->attributes_ as $attr) {
            if (! array_key_exists($attr->key, $attributes)) {
                continue;
            }

            $value = $attributes[$attr->key];

            if ($value === null || $value === '') {
                $ad->attributeValues()->where('category_attribute_id', $attr->id)->delete();

                continue;
            }

            $ad->attributeValues()->updateOrCreate(
                ['category_attribute_id' => $attr->id],
                ['value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value]
            );
        }
    }

    // Stores each uploaded image on the 'public' disk (config/filesystems.php),
    // under storage/app/public/ads/{ad id}/, appended after any images the ad
    // already has (sort_order continues from the current count) — used both
    // on creation and when adding photos to an existing ad via addImages().
    // The very first image the ad ever gets is flagged as the cover; later
    // batches never touch an already-set cover.
    // Resized/compressed via ImageService (App\Services) before storing —
    // raw phone-camera uploads are several MB each otherwise.
    private function storeImages(Ad $ad, array $files, ImageService $imageService): void
    {
        $existingCount = $ad->images()->count();

        foreach (array_values($files) as $index => $file) {
            $path = $imageService->storeResized($file, "ads/{$ad->id}");

            $ad->images()->create([
                'path' => $path,
                'is_cover' => $existingCount === 0 && $index === 0,
                'sort_order' => $existingCount + $index,
            ]);
        }
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
