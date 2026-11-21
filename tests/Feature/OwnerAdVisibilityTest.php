<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

// Flutter team request (2026-11-22): the owner can open their own pending or
// rejected ad (to review and edit it before approval) and sees why it was
// rejected; nobody else can. Plus admin:grant/revoke for test admins.
class OwnerAdVisibilityTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    public function test_the_owner_can_open_their_own_pending_and_rejected_ads(): void
    {
        $owner = User::factory()->create();
        $pending = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);
        $rejected = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'rejected', 'rejection_reason' => 'صور غير واضحة']);

        Sanctum::actingAs($owner);
        $this->getJson("/api/ads/{$pending->id}")->assertOk()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.rejection_reason', null);
        $this->getJson("/api/ads/{$rejected->id}")->assertOk()->assertJsonPath('data.rejection_reason', 'صور غير واضحة');

        $this->assertSame(0, $pending->fresh()->views_count, "the owner's own visits don't count as views");
    }

    public function test_it_also_works_with_a_real_bearer_token_on_the_public_route(): void
    {
        $owner = User::factory()->create();
        $pending = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);

        $this->withToken($owner->createToken('app')->plainTextToken)->getJson("/api/ads/{$pending->id}")->assertOk();
    }

    public function test_guests_and_other_users_still_get_404_for_a_non_public_ad(): void
    {
        $ad = Ad::factory()->create(['status' => 'rejected', 'rejection_reason' => 'x']);

        $this->getJson("/api/ads/{$ad->id}")->assertNotFound();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/ads/{$ad->id}")->assertNotFound();
    }

    public function test_rejection_reason_is_in_my_ads_but_never_shown_to_others(): void
    {
        $owner = User::factory()->create();
        $rejected = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'rejected', 'rejection_reason' => 'Blurry photos']);
        $live = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);

        Sanctum::actingAs($owner);
        $mine = collect($this->getJson('/api/my/ads')->assertOk()->json('data'))->keyBy('id');
        $this->assertSame('Blurry photos', $mine[$rejected->id]['rejection_reason']);
        $this->assertNull($mine[$live->id]['rejection_reason']);

        Sanctum::actingAs(User::factory()->create());
        $this->assertArrayNotHasKey('rejection_reason', $this->getJson("/api/ads/{$live->id}")->json('data'));
        $this->assertArrayNotHasKey('rejection_reason', $this->getJson('/api/ads')->json('data.0'));
    }

    public function test_the_website_lets_the_owner_open_a_rejected_ad_with_the_reason(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'rejected', 'rejection_reason' => 'صور غير واضحة']);

        $this->get(route('site.ads.show', $ad))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('site.ads.show', $ad))->assertNotFound();
        $this->actingAs($owner)->get(route('site.ads.show', $ad))->assertOk()->assertSee('صور غير واضحة');
    }

    public function test_admin_grant_and_revoke_by_phone(): void
    {
        $this->adminUser();
        $tester = User::factory()->create(['phone' => '0999111222']);

        $this->artisan('admin:grant', ['phone' => '+963 999 111 222'])->assertExitCode(0);
        $this->assertTrue($tester->fresh()->hasRole('admin'));

        $this->artisan('admin:revoke', ['phone' => '0999111222'])->assertExitCode(0);
        $this->assertFalse($tester->fresh()->hasRole('admin'));

        $this->artisan('admin:grant', ['phone' => '0933999999'])->assertExitCode(1);
    }

    public function test_the_last_admin_cannot_be_revoked(): void
    {
        $admin = $this->adminUser();

        $this->artisan('admin:revoke', ['phone' => $admin->phone])->assertExitCode(1);
        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }
}
