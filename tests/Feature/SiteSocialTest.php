<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\AdApproved;
use App\Notifications\NewMessageReceived;
use App\Notifications\NewRatingReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

// Favorites, chat, notifications, profile, seller profiles, ratings and
// reports on the website — the browser counterparts of FavoriteTest,
// ChatTest, NotificationTest, RatingTest and ReportTest.
class SiteSocialTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_favorite_unfavorite_and_list_favorites(): void
    {
        $user = User::factory()->create();
        $ad = Ad::factory()->create(['status' => 'approved', 'title' => 'Saved sofa']);
        $pending = Ad::factory()->create(['status' => 'pending']);

        $this->actingAs($user)->post(route('site.ads.favorite', $ad))->assertRedirect();
        $this->actingAs($user)->get('/favorites')->assertOk()->assertSee('Saved sofa');

        $this->actingAs($user)->post(route('site.ads.favorite', $pending))->assertNotFound();

        $this->actingAs($user)->post(route('site.ads.unfavorite', $ad))->assertRedirect();
        $this->actingAs($user)->get('/favorites')->assertOk()->assertDontSee('Saved sofa');
    }

    public function test_a_buyer_can_start_a_conversation_and_exchange_messages(): void
    {
        Notification::fake();
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        $response = $this->actingAs($buyer)->post(route('site.ads.message', $ad));
        $conversation = Conversation::sole();
        $response->assertRedirect(route('site.messages.show', $conversation));

        // Starting it again returns the same conversation.
        $this->actingAs($buyer)->post(route('site.ads.message', $ad));
        $this->assertSame(1, Conversation::count());

        $this->actingAs($buyer)->post(route('site.messages.send', $conversation), ['body' => 'هل ما زال متاحًا؟'])
            ->assertRedirect(route('site.messages.show', $conversation));
        Notification::assertSentTo($seller, NewMessageReceived::class);

        $this->actingAs($seller)->get(route('site.messages.show', $conversation))->assertOk()->assertSee('هل ما زال متاحًا؟');
        $this->actingAs($seller)->get('/messages')->assertOk()->assertSee($buyer->name);
    }

    public function test_a_seller_cannot_message_themselves_and_outsiders_cannot_read_a_thread(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $stranger = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        $this->actingAs($seller)->post(route('site.ads.message', $ad))->assertSessionHasErrors('message');

        $conversation = Conversation::create(['ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id]);
        $this->actingAs($stranger)->get(route('site.messages.show', $conversation))->assertForbidden();
        $this->actingAs($stranger)->get(route('site.messages.poll', $conversation))->assertForbidden();
    }

    public function test_the_thread_poll_returns_only_newer_messages_as_json(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);
        $conversation = Conversation::create(['ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id]);
        $first = $conversation->messages()->create(['sender_id' => $buyer->id, 'body' => 'first']);
        $conversation->messages()->create(['sender_id' => $seller->id, 'body' => 'second']);

        $this->actingAs($buyer)->getJson(route('site.messages.poll', $conversation).'?after='.$first->id)
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'second')
            ->assertJsonPath('messages.0.mine', false);
    }

    public function test_notifications_are_listed_and_can_be_marked_read(): void
    {
        $user = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $user->id, 'status' => 'approved', 'title' => 'Approved thing']);
        $user->notify(new AdApproved($ad));
        $notification = $user->notifications()->sole();

        $this->actingAs($user)->get('/notifications')->assertOk()->assertSee('Approved thing');
        $this->assertNull($notification->read_at);

        $this->actingAs($user)->post(route('site.notifications.read', $notification->id))->assertRedirect(route('site.my.ads'));
        $this->assertNotNull($notification->fresh()->read_at);

        $user->notify(new AdApproved($ad));
        $this->actingAs($user)->post(route('site.notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_a_user_can_update_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee($user->name);

        $this->actingAs($user)->post('/profile', ['name' => 'اسم جديد', 'email' => 'new@example.com'])->assertRedirect();

        $user->refresh();
        $this->assertSame('اسم جديد', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function test_a_seller_profile_shows_their_approved_ads_and_accepts_ratings(): void
    {
        Notification::fake();
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved', 'title' => 'Live listing']);
        Ad::factory()->create(['user_id' => $seller->id, 'status' => 'pending', 'title' => 'Unreviewed listing']);

        $this->get(route('site.users.show', $seller))
            ->assertOk()
            ->assertSee($seller->name)
            ->assertSee('Live listing')
            ->assertDontSee('Unreviewed listing');

        $this->actingAs($buyer)->post(route('site.users.rate', $seller), ['score' => 4, 'comment' => 'بائع ممتاز'])->assertRedirect();
        $this->assertSame(4.0, (float) $seller->fresh()->rating_average);
        $this->assertSame(1, $seller->fresh()->rating_count);
        Notification::assertSentTo($seller, NewRatingReceived::class);

        $this->actingAs($seller)->post(route('site.users.rate', $seller), ['score' => 5])->assertSessionHasErrors('score');
    }

    public function test_a_user_can_report_an_ad_or_a_user_but_not_themselves(): void
    {
        $reporter = User::factory()->create();
        $other = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $other->id, 'status' => 'approved']);
        $own = Ad::factory()->create(['user_id' => $reporter->id, 'status' => 'approved']);

        $this->actingAs($reporter)->post(route('site.ads.report', $ad), ['reason' => 'spam'])->assertRedirect();
        $this->assertDatabaseHas('reports', ['reporter_id' => $reporter->id, 'reportable_id' => $ad->id, 'reason' => 'spam', 'status' => 'pending']);

        $this->actingAs($reporter)->post(route('site.ads.report', $own), ['reason' => 'spam'])->assertSessionHasErrors('reason');

        $this->actingAs($reporter)->post(route('site.users.report', $other), ['reason' => 'scam', 'details' => 'x'])->assertRedirect();
        $this->assertDatabaseHas('reports', ['reporter_id' => $reporter->id, 'reportable_id' => $other->id, 'reportable_type' => User::class]);

        $this->actingAs($reporter)->post(route('site.users.report', $reporter), ['reason' => 'scam'])->assertSessionHasErrors('reason');
    }

    public function test_guests_see_a_login_prompt_instead_of_actions_on_an_ad(): void
    {
        $ad = Ad::factory()->create(['status' => 'approved']);

        $this->get(route('site.ads.show', $ad))->assertOk()->assertSee('سجّل الدخول للمراسلة');
        $this->post(route('site.ads.favorite', $ad))->assertRedirect(route('site.login'));
    }
}
