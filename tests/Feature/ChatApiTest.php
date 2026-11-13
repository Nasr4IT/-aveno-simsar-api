<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Items C5, C6 and M9 from the Flutter team's backend request, plus the
// "can't message a deleted account" rule.
class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $seller;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->create();
        $this->seller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $this->seller->id, 'status' => 'approved']);
        $this->conversation = Conversation::create(['ad_id' => $ad->id, 'buyer_id' => $this->buyer->id, 'seller_id' => $this->seller->id, 'last_message_at' => now()]);
    }

    private function say(User $from, string $body)
    {
        return $this->conversation->messages()->create(['sender_id' => $from->id, 'body' => $body]);
    }

    public function test_the_conversation_list_has_a_last_message_and_an_unread_count(): void
    {
        $this->say($this->seller, 'Hello');
        $this->say($this->seller, 'Still there?');
        $this->say($this->buyer, 'Yes');
        $this->say($this->seller, 'Great');

        Sanctum::actingAs($this->buyer);
        $this->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.last_message.body', 'Great')
            ->assertJsonPath('data.0.last_message.sender_id', $this->seller->id)
            ->assertJsonPath('data.0.unread_count', 3);

        Sanctum::actingAs($this->seller);
        $this->getJson('/api/conversations')->assertJsonPath('data.0.unread_count', 1);
    }

    public function test_after_id_returns_only_newer_messages_and_order_desc_puts_the_latest_first(): void
    {
        $first = $this->say($this->seller, 'one');
        $this->say($this->buyer, 'two');
        $this->say($this->seller, 'three');
        Sanctum::actingAs($this->buyer);

        $newer = $this->getJson("/api/conversations/{$this->conversation->id}/messages?after_id={$first->id}")->assertOk();
        $this->assertSame(['two', 'three'], collect($newer->json('data'))->pluck('body')->all());
        $this->assertArrayNotHasKey('meta', $newer->json(), 'after_id is unpaginated');

        $desc = $this->getJson("/api/conversations/{$this->conversation->id}/messages?order=desc")->assertOk();
        $this->assertSame(['three', 'two', 'one'], collect($desc->json('data'))->pluck('body')->all());

        $default = $this->getJson("/api/conversations/{$this->conversation->id}/messages")->assertOk();
        $this->assertSame(['one', 'two', 'three'], collect($default->json('data'))->pluck('body')->all());
    }

    public function test_mark_read_marks_only_the_other_persons_messages(): void
    {
        $this->say($this->seller, 'from seller');
        $own = $this->say($this->buyer, 'from buyer');
        Sanctum::actingAs($this->buyer);

        $this->postJson("/api/conversations/{$this->conversation->id}/read")->assertOk()->assertJsonPath('data.marked', 1);
        $this->assertNull($own->fresh()->read_at);
        $this->getJson('/api/conversations')->assertJsonPath('data.0.unread_count', 0);
    }

    public function test_outsiders_cannot_read_or_mark_a_conversation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/conversations/{$this->conversation->id}/read")->assertForbidden();
        $this->getJson("/api/conversations/{$this->conversation->id}/messages?after_id=0")->assertForbidden();
    }

    public function test_nobody_can_message_a_deleted_account(): void
    {
        app(AccountService::class)->delete($this->seller);

        Sanctum::actingAs($this->buyer);
        $this->postJson("/api/conversations/{$this->conversation->id}/messages", ['body' => 'hello?'])->assertStatus(422);

        $this->actingAs($this->buyer)->get(route('site.messages.show', $this->conversation))
            ->assertOk()
            ->assertSee(AccountService::DELETED_NAME)
            ->assertDontSee('name="body"', false);
    }
}
