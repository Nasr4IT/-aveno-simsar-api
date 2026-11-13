<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Favorite;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\AdApproved;
use App\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Items M6, M7, M10, M11 and C1–C3 from the Flutter team's backend request.
class ProfilesAndListsTest extends TestCase
{
    use RefreshDatabase;

    // ---- M6: public profile ----

    public function test_a_public_profile_shows_counts_but_no_contact_details(): void
    {
        $seller = User::factory()->create(['name' => 'Seller One', 'email' => 's@example.com']);
        Ad::factory()->count(2)->create(['user_id' => $seller->id, 'status' => 'approved']);
        Ad::factory()->create(['user_id' => $seller->id, 'status' => 'sold']);
        Ad::factory()->create(['user_id' => $seller->id, 'status' => 'pending']);

        $data = $this->getJson("/api/users/{$seller->id}")->assertOk()->json('data');

        $this->assertSame('Seller One', $data['name']);
        $this->assertSame(2, $data['active_ads_count']);
        $this->assertSame(1, $data['sold_ads_count']);
        $this->assertArrayHasKey('governorate', $data);
        $this->assertArrayNotHasKey('phone', $data);
        $this->assertArrayNotHasKey('email', $data);
    }

    public function test_banned_and_deleted_users_have_no_public_profile(): void
    {
        $banned = User::factory()->create(['is_banned' => true]);
        $deleted = User::factory()->create();
        $deleted->delete();

        $this->getJson("/api/users/{$banned->id}")->assertNotFound();
        $this->getJson("/api/users/{$deleted->id}")->assertNotFound();
        $this->get("/users/{$banned->id}")->assertNotFound();
    }

    // ---- M7: one seller's ads ----

    public function test_ads_can_be_filtered_by_seller(): void
    {
        $seller = User::factory()->create();
        Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved', 'title' => 'Mine']);
        Ad::factory()->create(['user_id' => $seller->id, 'status' => 'pending', 'title' => 'Mine pending']);
        Ad::factory()->create(['status' => 'approved', 'title' => 'Someone else']);

        $titles = collect($this->getJson("/api/ads?user_id={$seller->id}")->assertOk()->json('data'))->pluck('title')->all();

        $this->assertSame(['Mine'], $titles);
    }

    // ---- C1 / C2 / C3 ----

    public function test_ads_carry_is_favorited_for_the_viewer_and_false_for_guests(): void
    {
        $user = User::factory()->create();
        $liked = Ad::factory()->create(['status' => 'approved']);
        $other = Ad::factory()->create(['status' => 'approved']);
        Favorite::create(['user_id' => $user->id, 'ad_id' => $liked->id]);

        $this->getJson("/api/ads/{$liked->id}")->assertJsonPath('data.is_favorited', false);

        Sanctum::actingAs($user);
        $flags = collect($this->getJson('/api/ads')->json('data'))->pluck('is_favorited', 'id');
        $this->assertTrue($flags[$liked->id]);
        $this->assertFalse($flags[$other->id]);
        $this->getJson("/api/ads/{$liked->id}")->assertJsonPath('data.is_favorited', true);
        $this->getJson('/api/favorites')->assertJsonPath('data.0.is_favorited', true);
    }

    public function test_is_favorited_works_with_a_real_bearer_token_on_the_public_feed(): void
    {
        $user = User::factory()->create();
        $ad = Ad::factory()->create(['status' => 'approved']);
        Favorite::create(['user_id' => $user->id, 'ad_id' => $ad->id]);

        $this->withToken($user->createToken('app')->plainTextToken)
            ->getJson('/api/ads')->assertJsonPath('data.0.is_favorited', true);
    }

    public function test_list_items_carry_a_small_seller_object_and_location(): void
    {
        $seller = User::factory()->create(['name' => 'Card Seller']);
        Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        $item = $this->getJson('/api/ads')->assertOk()->json('data.0');

        $this->assertSame('Card Seller', $item['user']['name']);
        $this->assertArrayHasKey('avatar_url', $item['user']);
        $this->assertArrayNotHasKey('phone', $item['user']);
        $this->assertArrayHasKey('category', $item);
        $this->assertArrayHasKey('city', $item);
        $this->assertArrayHasKey('governorate', $item);
    }

    public function test_favorites_and_my_ads_load_category_and_location(): void
    {
        $user = User::factory()->create();
        Favorite::create(['user_id' => $user->id, 'ad_id' => Ad::factory()->create(['status' => 'approved'])->id]);
        Ad::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        foreach (['/api/favorites', '/api/my/ads'] as $url) {
            $item = $this->getJson($url)->assertOk()->json('data.0');
            foreach (['category', 'city', 'governorate', 'images'] as $key) {
                $this->assertArrayHasKey($key, $item, "{$url} → {$key}");
            }
        }
    }

    // ---- M10 / M11 ----

    public function test_unread_notification_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new AdApproved(Ad::factory()->create(['user_id' => $user->id])));
        $user->notify(new AdApproved(Ad::factory()->create(['user_id' => $user->id])));
        Sanctum::actingAs($user);

        $this->getJson('/api/notifications/unread-count')->assertOk()->assertExactJson(['data' => ['count' => 2]]);
        $this->postJson('/api/notifications/read-all');
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('data.count', 0);
    }

    public function test_ratings_summary_counts_each_star(): void
    {
        $seller = User::factory()->create();
        foreach ([5, 5, 4, 1] as $score) {
            Rating::create(['rated_user_id' => $seller->id, 'rater_user_id' => User::factory()->create()->id, 'score' => $score]);
        }
        app(RatingService::class)->recomputeForUser($seller);

        $this->getJson("/api/users/{$seller->id}/ratings/summary")
            ->assertOk()
            ->assertExactJson(['data' => ['average' => 3.75, 'count' => 4, 'stars' => ['5' => 2, '4' => 1, '3' => 0, '2' => 0, '1' => 1]]]);

        $this->get("/users/{$seller->id}")->assertOk()->assertSee('5★');
    }
}
