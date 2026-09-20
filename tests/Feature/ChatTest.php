<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\NewMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_buyer_can_start_a_conversation_about_an_ad(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        Sanctum::actingAs($buyer);
        $response = $this->postJson("/api/ads/{$ad->id}/conversations");

        $response->assertSuccessful();
        $this->assertDatabaseHas('conversations', ['ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id]);
    }

    // Regression test: an ad that isn't approved yet is invisible to
    // everyone but its owner everywhere else in the API (GET /ads/{id},
    // favoriting) — starting a conversation about one used to be the one
    // exception, letting a buyer message about a pending/rejected ad if
    // they somehow knew its id.
    public function test_a_pending_ad_cannot_be_messaged_about(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'pending']);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/ads/{$ad->id}/conversations")->assertNotFound();
    }

    public function test_a_user_cannot_start_a_conversation_with_themselves(): void
    {
        $seller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        Sanctum::actingAs($seller);
        $this->postJson("/api/ads/{$ad->id}/conversations")->assertStatus(422);
    }

    public function test_starting_a_conversation_twice_returns_the_same_one(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        Sanctum::actingAs($buyer);
        $first = $this->postJson("/api/ads/{$ad->id}/conversations")->json('data.id');
        $second = $this->postJson("/api/ads/{$ad->id}/conversations")->json('data.id');

        $this->assertSame($first, $second);
        $this->assertSame(1, Conversation::count());
    }

    public function test_participants_can_exchange_messages_but_outsiders_cannot_read_them(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $outsider = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);
        $conversation = Conversation::create(['ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id]);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Is this still for sale?'])
            ->assertSuccessful();

        Sanctum::actingAs($seller);
        $this->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        Sanctum::actingAs($outsider);
        $this->getJson("/api/conversations/{$conversation->id}/messages")->assertForbidden();
        $this->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'butting in'])->assertForbidden();
    }

    public function test_sending_a_message_notifies_the_other_participant(): void
    {
        Notification::fake();

        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);
        $conversation = Conversation::create(['ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id]);

        Sanctum::actingAs($buyer);
        $this->postJson("/api/conversations/{$conversation->id}/messages", ['body' => 'Hello'])->assertSuccessful();

        Notification::assertSentTo($seller, NewMessageReceived::class);
        Notification::assertNotSentTo($buyer, NewMessageReceived::class);
    }
}
