<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Favorite;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

// Items S1–S5 from the Flutter team's backend request (2026-11-11):
// rate limiting, contact-detail privacy, phone normalisation, the favorites
// status filter and the rating_count type.
class ApiSecurityTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    // ---- S3: one canonical phone format ----

    public function test_phone_numbers_are_normalised_from_every_common_spelling(): void
    {
        foreach (['0999111222', '+963999111222', '+963 999 111 222', '00963999111222', '963999111222', '999111222', '0999-111-222', '٠٩٩٩١١١٢٢٢'] as $spelling) {
            $this->assertSame('0999111222', PhoneNumber::normalize($spelling), $spelling);
        }
    }

    public function test_register_stores_the_canonical_phone_and_login_accepts_any_spelling(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'Sami', 'phone' => '+963 999 111 222', 'password' => 'secret123'])
            ->assertCreated()
            ->assertJsonPath('user.phone', '0999111222');

        $this->postJson('/api/auth/login', ['phone' => '00963999111222', 'password' => 'secret123'])->assertOk();
        $this->postJson('/api/auth/login', ['phone' => '0999111222', 'password' => 'secret123'])->assertOk();
    }

    public function test_the_same_number_in_another_spelling_cannot_open_a_second_account(): void
    {
        User::factory()->create(['phone' => '0999111222']);

        $this->postJson('/api/auth/register', ['name' => 'Twin', 'phone' => '+963999111222', 'password' => 'secret123'])
            ->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_a_non_syrian_mobile_number_is_rejected(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'X', 'phone' => '12345', 'password' => 'secret123'])
            ->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_website_login_also_normalises_the_phone(): void
    {
        $user = User::factory()->create(['phone' => '0999111222']);

        $this->post('/login', ['phone' => '+963 999 111 222', 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    // ---- S2: contact details are private ----

    public function test_a_sellers_phone_and_email_are_not_exposed_on_their_ad(): void
    {
        $seller = User::factory()->create(['email' => 'seller@example.com']);
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        $user = $this->getJson("/api/ads/{$ad->id}")->assertOk()->json('data.user');
        $this->assertArrayNotHasKey('phone', $user);
        $this->assertArrayNotHasKey('email', $user);

        Sanctum::actingAs(User::factory()->create());
        $user = $this->getJson("/api/ads/{$ad->id}")->assertOk()->json('data.user');
        $this->assertArrayNotHasKey('phone', $user);
    }

    public function test_the_owner_and_admins_still_see_contact_details(): void
    {
        // adminUser() before the first Sanctum::actingAs() — see AdminUserTest.
        $admin = $this->adminUser();
        $user = User::factory()->create(['phone' => '0999111222', 'email' => 'me@example.com']);

        Sanctum::actingAs($user);
        $this->getJson('/api/auth/me')->assertJsonPath('data.phone', '0999111222')->assertJsonPath('data.email', 'me@example.com');

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/users?q=0999111222')->assertOk()->assertJsonPath('data.0.phone', '0999111222');
    }

    // ---- S1: rate limiting ----

    public function test_login_is_rate_limited_after_five_attempts_a_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['phone' => '0999111222', 'password' => 'wrong'])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', ['phone' => '0999111222', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_the_website_login_is_rate_limited_too(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['phone' => '0999111222', 'password' => 'wrong']);
        }

        $this->post('/login', ['phone' => '0999111222', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_every_api_response_carries_rate_limit_headers(): void
    {
        $this->getJson('/api/categories')->assertOk()->assertHeader('X-RateLimit-Limit', 120);
    }

    // ---- S4 / S5 ----

    public function test_favorites_hide_ads_that_went_back_to_pending_or_were_rejected(): void
    {
        $user = User::factory()->create();
        foreach (['approved', 'sold', 'pending', 'rejected'] as $status) {
            Favorite::create(['user_id' => $user->id, 'ad_id' => Ad::factory()->create(['status' => $status, 'title' => "Ad {$status}"])->id]);
        }

        Sanctum::actingAs($user);
        $titles = collect($this->getJson('/api/favorites')->assertOk()->json('data'))->pluck('title')->sort()->values()->all();

        $this->assertSame(['Ad approved', 'Ad sold'], $titles);
    }

    public function test_register_returns_rating_count_as_an_integer(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'New', 'phone' => '0933000111', 'password' => 'secret123'])
            ->assertCreated()
            ->assertJsonPath('user.rating_count', 0);
    }
}
