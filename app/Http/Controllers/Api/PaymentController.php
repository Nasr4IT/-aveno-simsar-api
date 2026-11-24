<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdPackageResource;
use App\Http\Resources\PaymentResource;
use App\Models\Ad;
use App\Models\AdPackage;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\FeaturePaymentService;
use Illuminate\Http\Request;

/**
 * Featured-ad packages paid manually through Sham Cash (see
 * App\Services\FeaturePaymentService and docs/API_CONTRACT.md § Payments):
 * the seller transfers the price in the Sham Cash app, uploads the receipt,
 * and an admin approves or rejects the request in the admin panel.
 */
class PaymentController extends Controller
{
    // GET /api/ad-packages — the packages, plus where to pay (meta.shamcash).
    public function packages()
    {
        return AdPackageResource::collection(AdPackage::where('is_active', true)->orderBy('duration_days')->get())
            ->additional(['meta' => ['shamcash' => Setting::shamCash()]]);
    }

    // GET /api/payments/shamcash — the Sham Cash account to pay to.
    public function shamCashInfo()
    {
        return response()->json(['data' => Setting::shamCash()]);
    }

    // POST /api/ads/{ad}/feature-requests (multipart)
    public function store(Request $request, Ad $ad, FeaturePaymentService $payments)
    {
        $data = $request->validate([
            'ad_package_id' => ['required', 'integer', 'exists:ad_packages,id'],
            'receipt' => ['required', 'image', 'max:8192'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = $payments->request(
            $request->user(), $ad, AdPackage::findOrFail($data['ad_package_id']),
            $request->file('receipt'), $data['transaction_reference'] ?? null, $data['note'] ?? null,
        );

        return (new PaymentResource($payment->load(['ad', 'adPackage'])))->response()->setStatusCode(201);
    }

    // GET /api/my/payments — the caller's feature requests, newest first.
    public function mine(Request $request)
    {
        return PaymentResource::collection(
            Payment::where('user_id', $request->user()->id)->with(['ad', 'adPackage'])->latest()->paginate(20)
        );
    }

    // GET /api/payments/{payment}/receipt — the image; its seller or an admin only.
    public function receipt(Request $request, Payment $payment, FeaturePaymentService $payments)
    {
        return $payments->receiptResponse($payment, $request->user());
    }
}
