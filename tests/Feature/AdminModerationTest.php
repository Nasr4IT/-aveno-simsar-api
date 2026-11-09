<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\User;
use App\Notifications\AdApproved;
use App\Notifications\AdRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    public function test_non_admin_cannot_access_admin_ad_routes(): void
    {
        $user = User::factory()->create();
        $ad = Ad::factory()->create(['status' => 'pending']);

        Sanctum::actingAs($user);
        $this->getJson('/api/admin/ads')->assertForbidden();
        $this->postJson("/api/admin/ads/{$ad->id}/approve")->assertForbidden();
    }

    public function test_admin_can_list_ads_filtered_by_status(): void
    {
        Ad::factory()->create(['status' => 'pending']);
        Ad::factory()->create(['status' => 'approved']);

        Sanctum::actingAs($this->adminUser());
        $this->getJson('/api/admin/ads?status=pending')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_approve_a_pending_ad_and_the_owner_is_notified(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);

        Sanctum::actingAs($this->adminUser());
        $this->postJson("/api/admin/ads/{$ad->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertSame('approved', $ad->fresh()->status);
        Notification::assertSentTo($owner, AdApproved::class);
    }

    public function test_admin_can_reject_a_pending_ad_with_a_reason_and_the_owner_is_notified(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'pending']);

        Sanctum::actingAs($this->adminUser());
        $this->postJson("/api/admin/ads/{$ad->id}/reject", ['reason' => 'صور غير واضحة'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertSame('rejected', $ad->fresh()->status);
        $this->assertSame('صور غير واضحة', $ad->fresh()->rejection_reason);
        Notification::assertSentTo($owner, AdRejected::class);
    }

    public function test_rejecting_an_ad_requires_a_reason(): void
    {
        $ad = Ad::factory()->create(['status' => 'pending']);

        Sanctum::actingAs($this->adminUser());
        $this->postJson("/api/admin/ads/{$ad->id}/reject", [])->assertStatus(422);
    }

    // Regression test: approve()/reject() used to have no status guard, so
    // an admin could "reject" an ad that had already sold — firing a
    // confusing "your ad was rejected" notification at an owner whose item
    // already sold. Sold/expired ads are outside the review workflow.
    public function test_a_sold_ad_cannot_be_approved_or_rejected(): void
    {
        $admin = $this->adminUser();
        $ad = Ad::factory()->create(['status' => 'sold']);

        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/ads/{$ad->id}/approve")->assertStatus(422);
        $this->postJson("/api/admin/ads/{$ad->id}/reject", ['reason' => 'test'])->assertStatus(422);

        $this->assertSame('sold', $ad->fresh()->status);
    }
}
