<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// See docs/HOW_IT_WORKS.md § Sponsored Banners. A business pays the admin
// directly (outside the ad-posting / Sham Cash flow entirely) and the
// admin uploads a banner for the agreed window — there's no in-app payment
// tracking for this, same as the admin manually trusts a Sham Cash
// transfer happened before creating one.
class AdminBannerController extends Controller
{
    // GET /admin/banners — every banner regardless of active window, for
    // the admin's own management view (unlike the public /banners feed).
    public function index()
    {
        return BannerResource::collection(Banner::latest()->paginate(30));
    }

    // POST /admin/banners — multipart (carries the banner image).
    public function store(StoreBannerRequest $request, ImageService $imageService)
    {
        $data = $request->validated();

        $banner = Banner::create([
            ...$data,
            'image_path' => $imageService->storeResized($request->file('image'), 'banners'),
            'sort_order' => $data['sort_order'] ?? 0,
            'created_by' => $request->user()->id,
        ]);

        return (new BannerResource($banner))->response()->setStatusCode(201);
    }

    // PUT/PATCH /admin/banners/{banner} — fields only; replacing the image
    // itself isn't supported (delete + recreate instead — these are
    // short-lived promotional entries, not worth the multipart-PUT
    // workaround ads' image endpoints needed).
    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:150'],
            'link_url' => ['sometimes', 'nullable', 'string', 'max:500', StoreBannerRequest::LINK_URL_RULE],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $banner->update($data);

        return new BannerResource($banner);
    }

    public function destroy(Banner $banner)
    {
        Storage::disk(config('filesystems.default'))->delete($banner->image_path);
        $banner->delete();

        return response()->json(['message' => 'تم الحذف']);
    }
}
