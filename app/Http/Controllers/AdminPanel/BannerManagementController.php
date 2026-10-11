<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Models\Banner;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerManagementController extends Controller
{
    public function index()
    {
        return view('admin.banners.index', ['banners' => Banner::latest()->paginate(30)]);
    }

    public function store(StoreBannerRequest $request, ImageService $imageService)
    {
        $data = $request->validated();

        Banner::create([
            ...$data,
            'image_path' => $imageService->storeResized($request->file('image'), 'banners'),
            'sort_order' => $data['sort_order'] ?? 0,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'تمت إضافة البانر.');
    }

    // The on/off switch separate from the banner's date window — the same
    // is_active field Api\Admin\AdminBannerController@update takes. The
    // form sends the state its button was labelled with rather than this
    // flipping whatever is stored now, so a double-click (or two admins
    // both clicking "pause") still leaves the banner paused.
    public function setActive(Request $request, Banner $banner)
    {
        $banner->update($request->validate(['is_active' => ['required', 'boolean']]));

        return back()->with('status', $banner->is_active ? 'تم تفعيل البانر.' : 'تم إيقاف البانر.');
    }

    public function destroy(Banner $banner)
    {
        Storage::disk(config('filesystems.default'))->delete($banner->image_path);
        $banner->delete();

        return back()->with('status', 'تم الحذف.');
    }
}
