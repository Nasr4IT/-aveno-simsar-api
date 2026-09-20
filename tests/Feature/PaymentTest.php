<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_ad_packages_are_publicly_listable(): void
    {
        AdPackage::create(['name_ar' => 'باقة 15 يوم', 'duration_days' => 15, 'price' => 5, 'currency' => 'USD', 'is_active' => true]);
        AdPackage::create(['name_ar' => 'باقة قديمة', 'duration_days' => 7, 'price' => 3, 'currency' => 'USD', 'is_active' => false]);

        $response = $this->getJson('/api/ad-packages');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    // checkout() and webhook() are documented stubs awaiting real Sham Cash
    // API docs (see app/Services/ShamCash/ShamCashClient.php) — this locks in
    // that they fail loudly with 501 rather than crashing or silently
    // no-op-ing, so a future implementer sees a clear regression if that
    // changes unexpectedly.
    public function test_checkout_and_webhook_are_not_yet_implemented(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);
        $package = AdPackage::create(['name_ar' => 'باقة', 'duration_days' => 15, 'price' => 5, 'currency' => 'USD', 'is_active' => true]);

        Sanctum::actingAs($owner);
        $this->postJson("/api/ads/{$ad->id}/checkout", ['ad_package_id' => $package->id])
            ->assertStatus(501);

        $this->postJson('/api/payments/shamcash/webhook', [])->assertStatus(501);
    }
}
