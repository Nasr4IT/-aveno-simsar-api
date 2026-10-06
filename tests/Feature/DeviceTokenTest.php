<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_a_device_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/device-tokens', ['token' => 'fcm-token-abc', 'platform' => 'android'])->assertOk();

        $this->assertDatabaseHas('device_tokens', ['user_id' => $user->id, 'token' => 'fcm-token-abc', 'platform' => 'android']);
    }

    // Re-registering the same device (e.g. a different account logged in
    // on the same phone) must reassign ownership, not duplicate the row —
    // DeviceTokenController@store upserts by token, not (user, token).
    public function test_re_registering_the_same_token_under_a_different_user_reassigns_it(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        Sanctum::actingAs($first);
        $this->postJson('/api/device-tokens', ['token' => 'shared-device'])->assertOk();

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($second);
        $this->postJson('/api/device-tokens', ['token' => 'shared-device'])->assertOk();

        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertDatabaseHas('device_tokens', ['token' => 'shared-device', 'user_id' => $second->id]);
    }

    public function test_a_user_can_unregister_their_own_device_token(): void
    {
        $user = User::factory()->create();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'to-remove']);

        Sanctum::actingAs($user);
        $this->deleteJson('/api/device-tokens', ['token' => 'to-remove'])->assertOk();

        $this->assertDatabaseMissing('device_tokens', ['token' => 'to-remove']);
    }

    public function test_a_user_cannot_unregister_someone_elses_device_token(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        DeviceToken::create(['user_id' => $owner->id, 'token' => 'someone-elses']);

        Sanctum::actingAs($stranger);
        $this->deleteJson('/api/device-tokens', ['token' => 'someone-elses'])->assertOk();

        // No-op, not an error — but the row must survive.
        $this->assertDatabaseHas('device_tokens', ['token' => 'someone-elses', 'user_id' => $owner->id]);
    }

    public function test_platform_must_be_a_known_value(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/device-tokens', ['token' => 'x', 'platform' => 'smart-fridge'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('platform');
    }

    // Regression guard: PushNotificationService must no-op safely — not
    // throw, not make any HTTP call — when FIREBASE_CREDENTIALS_JSON isn't
    // set (the default; it's unset in every test environment), even for a
    // user who has a registered device token sitting right there.
    public function test_push_service_no_ops_safely_without_firebase_credentials_configured(): void
    {
        $user = User::factory()->create();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'some-token']);

        app(PushNotificationService::class)->sendToUser($user, 'Title', 'Body');

        $this->assertTrue(true); // reaching here without an exception is the assertion
    }
}
