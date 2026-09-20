<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_favorite_and_unfavorite_an_ad(): void
    {
        $user = User::factory()->create();
        $ad = Ad::factory()->create(['status' => 'approved']);
        Sanctum::actingAs($user);

        $this->postJson("/api/ads/{$ad->id}/favorite")->assertOk();
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'ad_id' => $ad->id]);

        $this->getJson('/api/favorites')->assertOk()->assertJsonCount(1, 'data');

        $this->deleteJson("/api/ads/{$ad->id}/favorite")->assertOk();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'ad_id' => $ad->id]);
    }

    public function test_favorites_list_only_shows_the_current_users_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ad = Ad::factory()->create(['status' => 'approved']);

        $otherUser->favorites()->create(['ad_id' => $ad->id]);

        Sanctum::actingAs($user);
        $this->getJson('/api/favorites')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_pending_ad_cannot_be_favorited(): void
    {
        $user = User::factory()->create();
        $ad = Ad::factory()->create(['status' => 'pending']);

        Sanctum::actingAs($user);
        $this->postJson("/api/ads/{$ad->id}/favorite")->assertNotFound();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'ad_id' => $ad->id]);
    }
}
