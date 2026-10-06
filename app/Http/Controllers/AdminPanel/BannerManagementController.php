<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerManagementController extends Controller
{
    private const LINK_URL_RULE = 'regex:/^(https?:\/\/|tel:).+/';

    public function index()
    {
        return view('admin.banners.index', ['banners' => Banner::latest()->paginate(30)]);
    }

    public function store(Request $request, ImageService $imageService)
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'title' => ['nullable', 'string', 'max:150'],
            'link_url' => ['nullable', 'string', 'max:500', self::LINK_URL_RULE],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Banner::create([
            ...$data,
            'image_path' => $imageService->storeResized($request->file('image'), 'banners'),
            'sort_order' => $data['sort_order'] ?? 0,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'تمت إضافة البانر.');
    }

    // The on/off switch separate from the ad-boost's date window — same
    // single field Api\Admin\AdminBannerController@update exposes as
    // is_active, just toggled from its current value instead of taking an
    // explicit payload (simpler for a plain form button).
    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return back()->with('status', $banner->is_active ? 'تم تفعيل البانر.' : 'تم إيقاف البانر.');
    }

    public function destroy(Banner $banner)
    {
        Storage::disk(config('filesystems.default'))->delete($banner->image_path);
        $banner->delete();

        return back()->with('status', 'تم الحذف.');
    }
}
