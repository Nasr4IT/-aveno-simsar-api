<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Governorate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Item C7 from the Flutter team's backend request: is_negotiable, address,
// allow_chat, allow_calls, hide_phone — and what they control.
class AdOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function adPayload(array $extra = []): array
    {
        $gov = Governorate::create(['name_ar' => 'دمشق', 'name_en' => 'Damascus', 'is_active' => true]);
        $city = $gov->cities()->create(['name_ar' => 'المزة', 'name_en' => 'Mezzeh', 'is_active' => true]);

        return [
            'category_id' => Category::factory()->create()->id,
            'governorate_id' => $gov->id,
            'city_id' => $city->id,
            'title' => 'Sofa',
            'description' => 'Good condition',
            'images' => [UploadedFile::fake()->image('a.jpg')],
            ...$extra,
        ];
    }

    public function test_new_ads_get_the_defaults_and_accept_the_options(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/ads', $this->adPayload())->assertCreated()
            ->assertJsonPath('data.is_negotiable', false)
            ->assertJsonPath('data.allow_chat', true)
            ->assertJsonPath('data.allow_calls', true)
            ->assertJsonPath('data.hide_phone', false)
            ->assertJsonPath('data.address', null);

        $this->postJson('/api/ads', $this->adPayload([
            'is_negotiable' => true, 'address' => 'Al-Jalaa St', 'allow_chat' => false, 'allow_calls' => false, 'hide_phone' => true,
        ]))->assertCreated()
            ->assertJsonPath('data.is_negotiable', true)
            ->assertJsonPath('data.address', 'Al-Jalaa St')
            ->assertJsonPath('data.allow_chat', false)
            ->assertJsonPath('data.allow_calls', false)
            ->assertJsonPath('data.hide_phone', true);
    }

    public function test_contact_phone_is_shown_only_when_allowed_and_only_to_logged_in_users(): void
    {
        $seller = User::factory()->create(['phone' => '0999111222']);
        $open = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);
        $hidden = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved', 'hide_phone' => true]);
        $noCalls = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved', 'allow_calls' => false]);

        $this->getJson("/api/ads/{$open->id}")->assertJsonPath('data.contact_phone', null);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/ads/{$open->id}")->assertJsonPath('data.contact_phone', '0999111222');
        $this->getJson("/api/ads/{$hidden->id}")->assertJsonPath('data.contact_phone', null);
        $this->getJson("/api/ads/{$noCalls->id}")->assertJsonPath('data.contact_phone', null);
        $this->assertArrayNotHasKey('contact_phone', $this->getJson('/api/ads')->json('data.0'), 'detail only');
    }

    public function test_chat_can_be_turned_off_for_new_conversations(): void
    {
        $buyer = User::factory()->create();
        $ad = Ad::factory()->create(['status' => 'approved', 'allow_chat' => false]);
        Sanctum::actingAs($buyer);

        $this->postJson("/api/ads/{$ad->id}/conversations")->assertStatus(422);

        // A conversation that already existed still opens.
        Conversation::create(['ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $ad->user_id]);
        $this->postJson("/api/ads/{$ad->id}/conversations")->assertOk();
    }

    public function test_changing_only_contact_options_does_not_send_the_ad_back_for_review(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);
        Sanctum::actingAs($owner);

        $this->patchJson("/api/ads/{$ad->id}", ['hide_phone' => true, 'allow_chat' => false])->assertOk()
            ->assertJsonPath('data.status', 'approved')->assertJsonPath('data.hide_phone', true);

        $this->patchJson("/api/ads/{$ad->id}", ['is_negotiable' => true])->assertOk()
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_the_website_saves_and_shows_the_options(): void
    {
        $owner = User::factory()->create(['phone' => '0999111222']);
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);

        $this->actingAs($owner)->post(route('site.my.ads.update', $ad), [
            'title' => $ad->title, 'description' => 'desc',
            'is_negotiable' => '1', 'address' => 'Al-Jalaa St',
            'allow_chat' => '0', 'allow_calls' => '1', 'hide_phone' => '0',
        ])->assertRedirect();

        $ad->refresh();
        $this->assertTrue($ad->is_negotiable);
        $this->assertFalse($ad->allow_chat);
        $ad->update(['status' => 'approved']);

        $this->actingAs(User::factory()->create())->get(route('site.ads.show', $ad))
            ->assertOk()
            ->assertSee('Al-Jalaa St')
            ->assertSee('tel:0999111222', false)
            ->assertDontSee(route('site.ads.message', $ad), false);
    }
}
