<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdPackageResource;
use App\Models\Ad;
use App\Models\AdPackage;
use App\Models\Payment;
use App\Services\ShamCash\ShamCashClient;
use Illuminate\Http\Request;

/**
 * See docs/API_CONTRACT.md § Payments.
 * "تفعيل تلقائي للباقة عند إتمام الدفع عبر Sham Cash API" (proposal §2/§3).
 * checkout() opens a Sham Cash session; webhook() is the server-to-server
 * callback that marks the Payment paid and activates the ad's featured window.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly ShamCashClient $shamCash) {}

    // GET /api/ad-packages — the 15/30/60-day packages, for the "feature this ad" screen.
    public function packages()
    {
        return AdPackageResource::collection(AdPackage::where('is_active', true)->orderBy('duration_days')->get());
    }

    // POST /api/ads/{ad}/checkout  { ad_package_id }
    public function checkout(Request $request, Ad $ad)
    {
        abort_unless($ad->user_id === $request->user()->id, 403);

        $data = $request->validate(['ad_package_id' => ['required', 'exists:ad_packages,id']]);
        $package = AdPackage::findOrFail($data['ad_package_id']);

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'ad_id' => $ad->id,
            'ad_package_id' => $package->id,
            'amount' => $package->price,
            'currency' => $package->currency,
            'status' => 'pending',
        ]);

        // TODO: $checkoutUrl = $this->shamCash->createCheckout($payment);
        abort(501, 'Sham Cash integration pending — see app/Services/ShamCash/ShamCashClient.php');
    }

    // POST /api/payments/shamcash/webhook — public route, verified via HMAC signature.
    public function webhook(Request $request)
    {
        // TODO: verify $this->shamCash->verifyWebhookSignature($request), locate the
        // Payment by provider_reference, mark it paid, set ad.is_featured = true and
        // ad.featured_until = now()->addDays($payment->adPackage->duration_days).
        abort(501, 'Not implemented yet.');
    }
}
