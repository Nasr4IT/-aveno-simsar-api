<?php

namespace App\Services\ShamCash;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Sham Cash payment API (config/services.php "shamcash").
 * Fill in against the real Sham Cash API docs before sprint 5 (proposal §7:
 * "نظام الإعلانات المدفوعة وربط Sham Cash API" — 1 week).
 */
class ShamCashClient
{
    private readonly string $baseUrl;

    private readonly string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.shamcash.base_url');
        $this->apiKey = config('services.shamcash.api_key');
    }

    // Opens a hosted checkout session for one Payment, returns the redirect URL.
    public function createCheckout(Payment $payment): string
    {
        // $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/checkout", [
        //     'merchant_id' => config('services.shamcash.merchant_id'),
        //     'amount' => $payment->amount,
        //     'currency' => $payment->currency,
        //     'reference' => (string) $payment->id,
        //     'callback_url' => config('services.shamcash.callback_url'),
        // ]);
        //
        // return $response->json('checkout_url');

        throw new \RuntimeException('ShamCashClient::createCheckout is a stub — implement against the real Sham Cash API.');
    }

    // Verifies the HMAC signature Sham Cash sends on the webhook request.
    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-ShamCash-Signature');
        $expected = hash_hmac('sha256', $request->getContent(), config('services.shamcash.webhook_secret'));

        return $signature && hash_equals($expected, $signature);
    }
}
