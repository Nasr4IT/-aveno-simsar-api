<?php

namespace Tests\Feature;

use App\Models\Rating;
use App\Models\User;
use App\Notifications\NewRatingReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_list_read_and_mark_all_their_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $rater = User::factory()->create();
        $rating = Rating::create(['rater_user_id' => $rater->id, 'rated_user_id' => $user->id, 'score' => 5]);
        $user->notify(new NewRatingReceived($rating));

        Sanctum::actingAs($user);

        $list = $this->getJson('/api/notifications')->assertOk();
        $this->assertCount(1, $list->json('data'));
        $this->assertNull($list->json('data.0.read_at'));

        $notificationId = $list->json('data.0.id');
        $this->postJson("/api/notifications/{$notificationId}/read")->assertOk();
        $this->assertNotNull($user->notifications()->find($notificationId)->read_at);

        $user->notify(new NewRatingReceived($rating));
        $this->postJson('/api/notifications/read-all')->assertOk();
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_a_user_only_sees_their_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $rater = User::factory()->create();
        $rating = Rating::create(['rater_user_id' => $rater->id, 'rated_user_id' => $otherUser->id, 'score' => 5]);
        $otherUser->notify(new NewRatingReceived($rating));

        Sanctum::actingAs($user);
        $this->getJson('/api/notifications')->assertOk()->assertJsonCount(0, 'data');
    }
}
