<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdPackage;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\FeatureRequestApproved;
use App\Notifications\FeatureRequestRejected;
use App\Services\FeaturePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

// Featured-ad packages paid manually through Sham Cash: the seller uploads
// the transfer receipt, an admin approves (ad featured) or rejects it.
class PaymentTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    private AdPackage $package;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->package = AdPackage::create(['name_ar' => 'إبراز 15 يوم', 'name_en' => 'Featured for 15 days', 'duration_days' => 15, 'price' => 5, 'currency' => 'USD', 'is_active' => true]);
        Setting::put(['shamcash_account_name' => 'Simsar', 'shamcash_account_number' => '0999000111', 'shamcash_instructions' => 'Pay the full amount']);
    }

    private function receipt(): UploadedFile
    {
        return UploadedFile::fake()->image('receipt.jpg', 600, 900);
    }

    private function request(User $seller, Ad $ad, array $extra = [])
    {
        Sanctum::actingAs($seller);

        return $this->post("/api/ads/{$ad->id}/feature-requests", [
            'ad_package_id' => $this->package->id, 'receipt' => $this->receipt(), 'transaction_reference' => 'TX-123', ...$extra,
        ], ['Accept' => 'application/json']);
    }

    public function test_packages_list_only_active_ones_and_include_where_to_pay(): void
    {
        AdPackage::create(['name_ar' => 'باقة قديمة', 'duration_days' => 7, 'price' => 3, 'currency' => 'USD', 'is_active' => false]);

        $this->getJson('/api/ad-packages')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name_en', 'Featured for 15 days')
            ->assertJsonPath('meta.shamcash.account_number', '0999000111');
        $this->getJson('/api/payments/shamcash')->assertOk()->assertJsonPath('data.account_name', 'Simsar');
    }

    public function test_a_seller_requests_featuring_with_a_receipt_and_it_waits_for_review(): void
    {
        $seller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        $this->request($seller, $ad, ['note' => 'Paid today'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.amount', 5)
            ->assertJsonPath('data.transaction_reference', 'TX-123')
            ->assertJsonPath('data.package.duration_days', 15);

        $payment = Payment::sole();
        Storage::disk('local')->assertExists($payment->receipt_path);
        $this->assertFalse($ad->fresh()->isFeaturedNow(), 'not featured until an admin approves');
        $this->getJson('/api/my/payments')->assertOk()->assertJsonPath('data.0.id', $payment->id);
    }

    public function test_one_pending_request_per_ad_and_only_for_own_approved_ads(): void
    {
        $seller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);
        $this->request($seller, $ad)->assertCreated();
        $this->request($seller, $ad)->assertStatus(422);

        $pendingAd = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'pending']);
        $this->request($seller, $pendingAd)->assertStatus(422);

        $someoneElses = Ad::factory()->create(['status' => 'approved']);
        $this->request($seller, $someoneElses)->assertStatus(422);

        $this->request($seller, Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']), ['receipt' => null])
            ->assertStatus(422)->assertJsonValidationErrors('receipt');
    }

    public function test_receipts_are_private_to_the_seller_and_admins(): void
    {
        $admin = $this->adminUser();
        $seller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);
        $this->request($seller, $ad)->assertCreated();
        $payment = Payment::sole();

        $this->get("/api/payments/{$payment->id}/receipt")->assertOk();
        Sanctum::actingAs(User::factory()->create());
        $this->get("/api/payments/{$payment->id}/receipt")->assertNotFound();
        $this->actingAs($admin)->get(route('admin.payments.receipt', $payment))->assertOk();
        $this->assertStringNotContainsString('/storage/', $payment->receipt_path, 'never on the public disk');
    }

    public function test_admin_approval_features_the_ad_for_the_package_days_and_notifies(): void
    {
        $admin = $this->adminUser();
        $seller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);
        $this->request($seller, $ad)->assertCreated();
        $payment = Payment::sole();

        $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk()->assertSee($ad->title);
        $this->actingAs($admin)->post(route('admin.payments.approve', $payment))->assertRedirect();

        $ad->refresh();
        $this->assertTrue($ad->isFeaturedNow());
        $this->assertEqualsWithDelta(now()->addDays(15)->timestamp, $ad->featured_until->timestamp, 60);
        $this->assertSame('paid', $payment->fresh()->status);
        Notification::assertSentTo($seller, FeatureRequestApproved::class);

        $this->getJson("/api/ads/{$ad->id}")->assertJsonPath('data.is_featured', true);
        $this->actingAs($admin)->post(route('admin.payments.approve', $payment))->assertSessionHasErrors('payment');
    }

    public function test_a_second_package_extends_the_remaining_featured_time(): void
    {
        $admin = $this->adminUser();
        $seller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved', 'is_featured' => true, 'featured_until' => now()->addDays(5)]);
        $this->request($seller, $ad)->assertCreated();

        app(FeaturePaymentService::class)->approve(Payment::sole(), $admin);

        $this->assertEqualsWithDelta(now()->addDays(20)->timestamp, $ad->fresh()->featured_until->timestamp, 60);
    }

    public function test_admin_rejection_keeps_the_ad_normal_and_tells_the_seller_why(): void
    {
        $admin = $this->adminUser();
        $seller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);
        $this->request($seller, $ad)->assertCreated();
        $payment = Payment::sole();

        $this->actingAs($admin)->post(route('admin.payments.reject', $payment), ['reason' => 'Amount not received'])->assertRedirect();

        $this->assertSame('rejected', $payment->fresh()->status);
        $this->assertFalse($ad->fresh()->isFeaturedNow());
        Notification::assertSentTo($seller, FeatureRequestRejected::class);
        Sanctum::actingAs($seller);
        $this->getJson('/api/my/payments')->assertJsonPath('data.0.rejection_reason', 'Amount not received');
        $this->request($seller, $ad)->assertCreated(); // can try again
    }

    public function test_featured_time_runs_out_by_itself(): void
    {
        $expired = Ad::factory()->create(['status' => 'approved', 'title' => 'Old featured', 'is_featured' => true, 'featured_until' => now()->subDay()]);
        $live = Ad::factory()->create(['status' => 'approved', 'title' => 'Live featured', 'is_featured' => true, 'featured_until' => now()->addDay()]);

        $this->getJson("/api/ads/{$expired->id}")->assertJsonPath('data.is_featured', false);
        $this->assertSame($live->id, $this->getJson('/api/ads')->json('data.0.id'), 'only the live one is pinned first');
    }

    public function test_the_website_feature_page_and_request(): void
    {
        $seller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        $this->actingAs($seller)->get(route('site.my.ads'))->assertSee(route('site.my.ads.feature', $ad), false);
        $this->actingAs($seller)->get(route('site.my.ads.feature', $ad))->assertOk()->assertSee('0999000111');
        $this->actingAs($seller)->post(route('site.my.ads.feature.store', $ad), ['ad_package_id' => $this->package->id, 'receipt' => $this->receipt()])
            ->assertRedirect(route('site.my.ads.feature', $ad));
        $this->assertSame(1, Payment::where('status', 'pending')->count());

        $this->actingAs(User::factory()->create())->get(route('site.my.ads.feature', $ad))->assertNotFound();
    }

    public function test_admin_settings_page_saves_the_sham_cash_account(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.settings.update'), [
            'shamcash_account_name' => 'New name', 'shamcash_account_number' => '0933111222', 'contact_email' => 'help@simsar-sy.com',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->getJson('/api/app-config')
            ->assertOk()
            ->assertJsonPath('data.shamcash.account_number', '0933111222')
            ->assertJsonPath('data.contact_email', 'help@simsar-sy.com')
            ->assertJsonPath('data.privacy_policy_url', route('site.privacy'));
    }
}
