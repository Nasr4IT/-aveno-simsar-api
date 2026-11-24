<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdPackage;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\FeaturePaymentService;
use Illuminate\Http\Request;

// "Feature my ad" on the website — same rules as the API's
// POST /ads/{ad}/feature-requests (FeaturePaymentService).
class FeatureController extends Controller
{
    public function show(Request $request, Ad $ad)
    {
        abort_unless($ad->isOwnedBy($request->user()), 404);

        return view('site.my.feature', [
            'ad' => $ad,
            'packages' => AdPackage::where('is_active', true)->orderBy('duration_days')->get(),
            'shamcash' => Setting::shamCash(),
            'history' => $ad->payments()->with('adPackage')->latest()->get(),
        ]);
    }

    public function store(Request $request, Ad $ad, FeaturePaymentService $payments)
    {
        abort_unless($ad->isOwnedBy($request->user()), 404);

        $data = $request->validate([
            'ad_package_id' => ['required', 'integer', 'exists:ad_packages,id'],
            'receipt' => ['required', 'image', 'max:8192'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payments->request(
            $request->user(), $ad, AdPackage::findOrFail($data['ad_package_id']),
            $request->file('receipt'), $data['transaction_reference'] ?? null, $data['note'] ?? null,
        );

        return redirect()->route('site.my.ads.feature', $ad)
            ->with('status', __('تم إرسال طلب التمييز — ستراجع الإدارة الإيصال وتبلغك بالنتيجة.'));
    }

    public function receipt(Request $request, Payment $payment, FeaturePaymentService $payments)
    {
        return $payments->receiptResponse($payment, $request->user());
    }
}
