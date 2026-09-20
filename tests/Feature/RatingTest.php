<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\User;
use App\Notifications\NewRatingReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_rate_another_user_and_their_average_is_recomputed(): void
    {
        $rater1 = User::factory()->create();
        $rater2 = User::factory()->create();
        $rated = User::factory()->create();

        Sanctum::actingAs($rater1);
        $this->postJson("/api/users/{$rated->id}/ratings", ['score' => 4])->assertSuccessful();

        Sanctum::actingAs($rater2);
        $this->postJson("/api/users/{$rated->id}/ratings", ['score' => 2])->assertSuccessful();

        $rated->refresh();
        $this->assertEquals(3.00, $rated->rating_average);
        $this->assertSame(2, $rated->rating_count);
    }

    public function test_a_user_cannot_rate_themselves(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $this->postJson("/api/users/{$user->id}/ratings", ['score' => 5])->assertStatus(422);
    }

    public function test_re_rating_the_same_user_updates_rather_than_duplicates(): void
    {
        $rater = User::factory()->create();
        $rated = User::factory()->create();

        Sanctum::actingAs($rater);
        $this->postJson("/api/users/{$rated->id}/ratings", ['score' => 2])->assertSuccessful();
        $this->postJson("/api/users/{$rated->id}/ratings", ['score' => 5])->assertSuccessful();

        $this->assertDatabaseCount('ratings', 1);
        $this->assertEquals(5.00, $rated->fresh()->rating_average);
    }

    public function test_rating_a_user_sends_them_a_notification(): void
    {
        Notification::fake();

        $rater = User::factory()->create();
        $rated = User::factory()->create();

        Sanctum::actingAs($rater);
        $this->postJson("/api/users/{$rated->id}/ratings", ['score' => 5])->assertSuccessful();

        Notification::assertSentTo($rated, NewRatingReceived::class);
    }

    // A rating doesn't require proof of an actual transaction — it can
    // reference any ad the rater plausibly saw (home feed or search), which
    // by definition means an approved one. A pending/rejected ad was never
    // shown to anyone, so it can't be cited either.
    public function test_rating_cannot_reference_a_non_approved_ad(): void
    {
        $rater = User::factory()->create();
        $rated = User::factory()->create();
        $pendingAd = Ad::factory()->create(['status' => 'pending']);

        Sanctum::actingAs($rater);
        $this->postJson("/api/users/{$rated->id}/ratings", ['score' => 5, 'ad_id' => $pendingAd->id])
            ->assertUnprocessable();
    }

    public function test_rating_can_reference_any_approved_ad_without_a_prior_transaction(): void
    {
        $rater = User::factory()->create();
        $rated = User::factory()->create();
        $approvedAd = Ad::factory()->create(['status' => 'approved']); // not owned by $rated, no conversation between them

        Sanctum::actingAs($rater);
        $this->postJson("/api/users/{$rated->id}/ratings", ['score' => 5, 'ad_id' => $approvedAd->id])
            ->assertSuccessful();
    }

    public function test_ratings_for_a_user_are_publicly_visible(): void
    {
        $rater = User::factory()->create();
        $rated = User::factory()->create();
        Sanctum::actingAs($rater);
        $this->postJson("/api/users/{$rated->id}/ratings", ['score' => 5, 'comment' => 'Great!'])->assertSuccessful();

        $this->getJson("/api/users/{$rated->id}/ratings")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.comment', 'Great!');
    }
}
