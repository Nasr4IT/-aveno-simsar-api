<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Favorite;
use App\Models\Governorate;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\PasswordResetCode;
use App\Services\AccountService;
use App\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Items M1, M3, M4, M5 (and C4) from the Flutter team's backend request:
// forgot/reset password by email, edit profile + avatar, change password,
// delete account — on the API and the website.
class AccountTest extends TestCase
{
    use RefreshDatabase;

    // ---- M1: forgot / reset password (email code) ----

    private function requestCode(User $user, array $identifier): string
    {
        Notification::fake();
        $this->postJson('/api/auth/forgot-password', $identifier)->assertOk();

        $code = null;
        Notification::assertSentTo($user, PasswordResetCode::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        return $code;
    }

    public function test_a_user_can_reset_their_password_with_the_emailed_code(): void
    {
        $user = User::factory()->create(['phone' => '0999111222', 'email' => 'sami@example.com']);
        $oldToken = $user->createToken('old')->plainTextToken;

        $code = $this->requestCode($user, ['phone' => '+963999111222']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->postJson('/api/auth/reset-password', ['phone' => '0999111222', 'code' => $code, 'password' => 'brand-new-pass'])->assertOk();

        $this->postJson('/api/auth/login', ['phone' => '0999111222', 'password' => 'brand-new-pass'])->assertOk();
        $this->assertSame(0, $user->tokens()->where('name', 'old')->count(), 'old tokens are revoked');
        $this->app['auth']->forgetGuards();
        $this->withToken($oldToken)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_the_code_can_be_requested_by_email_too_and_is_single_use(): void
    {
        $user = User::factory()->create(['email' => 'Sami@Example.com']);
        $code = $this->requestCode($user, ['email' => 'sami@example.com']);

        $this->postJson('/api/auth/reset-password', ['email' => 'sami@example.com', 'code' => $code, 'password' => 'brand-new-pass'])->assertOk();
        $this->postJson('/api/auth/reset-password', ['email' => 'sami@example.com', 'code' => $code, 'password' => 'another-pass'])
            ->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_a_wrong_or_expired_code_is_rejected(): void
    {
        $user = User::factory()->create(['phone' => '0999111222', 'email' => 'sami@example.com']);
        $code = $this->requestCode($user, ['phone' => '0999111222']);
        $wrong = $code === '111111' ? '222222' : '111111';

        $this->postJson('/api/auth/reset-password', ['phone' => '0999111222', 'code' => $wrong, 'password' => 'brand-new-pass'])
            ->assertStatus(422)->assertJsonValidationErrors('code');

        $this->travel(31)->minutes();
        $this->postJson('/api/auth/reset-password', ['phone' => '0999111222', 'code' => $code, 'password' => 'brand-new-pass'])
            ->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_forgot_password_answers_the_same_for_unknown_accounts_and_accounts_without_email(): void
    {
        Notification::fake();
        User::factory()->create(['phone' => '0999111222', 'email' => null]);

        $known = $this->postJson('/api/auth/forgot-password', ['phone' => '0999111222'])->assertOk()->json('message');
        $unknown = $this->postJson('/api/auth/forgot-password', ['phone' => '0933000000'])->assertOk()->json('message');

        $this->assertSame($known, $unknown);
        Notification::assertNothingSent();
    }

    public function test_the_website_forgot_password_flow_works(): void
    {
        Notification::fake();
        $user = User::factory()->create(['phone' => '0999111222', 'email' => 'sami@example.com']);

        $this->post('/forgot-password', ['identifier' => 'sami@example.com'])->assertRedirect();
        $code = null;
        Notification::assertSentTo($user, PasswordResetCode::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        $this->post('/reset-password', ['identifier' => '0999111222', 'code' => $code, 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
            ->assertRedirect(route('site.login'));
        $this->post('/login', ['phone' => '0999111222', 'password' => 'brand-new-pass']);
        $this->assertAuthenticatedAs($user);
    }

    // ---- M3 / C4: edit profile ----

    public function test_a_user_can_update_their_profile_and_avatar(): void
    {
        Storage::fake('public');
        $gov = Governorate::create(['name_ar' => 'دمشق', 'name_en' => 'Damascus', 'is_active' => true]);
        $city = $gov->cities()->create(['name_ar' => 'المزة', 'name_en' => 'Mezzeh', 'is_active' => true]);
        $user = User::factory()->create(['name' => 'Old']);
        Sanctum::actingAs($user);

        $response = $this->post('/api/auth/me', [
            'name' => 'New Name',
            'governorate_id' => $gov->id,
            'city_id' => $city->id,
            'avatar' => UploadedFile::fake()->image('me.jpg', 400, 400),
        ], ['Accept' => 'application/json'])->assertOk();

        $response->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.governorate.name_en', 'Damascus')
            ->assertJsonPath('data.city.name_en', 'Mezzeh');
        $this->assertNotNull($response->json('data.avatar_url'));
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);

        // Partial JSON PUT: only the name changes; then remove the avatar.
        $this->putJson('/api/auth/me', ['name' => 'Renamed'])->assertOk()->assertJsonPath('data.city.id', $city->id);
        $path = $user->fresh()->avatar_path;
        $this->putJson('/api/auth/me', ['remove_avatar' => true])->assertOk()->assertJsonPath('data.avatar_url', null);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_profile_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/auth/me', ['email' => 'taken@example.com'])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    // ---- M4: change password ----

    public function test_change_password_keeps_this_device_and_signs_out_the_others(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('this-phone')->plainTextToken;
        $user->createToken('other-phone');

        $this->withToken($current)->postJson('/api/auth/change-password', ['current_password' => 'wrong', 'password' => 'new-password'])
            ->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->withToken($current)->postJson('/api/auth/change-password', ['current_password' => 'password', 'password' => 'new-password'])
            ->assertOk();

        $this->assertSame(['this-phone'], $user->tokens()->pluck('name')->all());
        $this->postJson('/api/auth/login', ['phone' => $user->phone, 'password' => 'new-password'])->assertOk();
    }

    public function test_the_website_can_change_the_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/password', ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(password_verify('new-password', $user->fresh()->password));
    }

    // ---- M5: delete account ----

    public function test_deleting_requires_the_correct_password(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/auth/me', ['password' => 'wrong'])->assertStatus(422)->assertJsonValidationErrors('password');
        $this->assertNotSoftDeleted($user);
    }

    public function test_deleting_an_account_removes_its_data_but_keeps_the_other_persons_chat(): void
    {
        $leaver = User::factory()->create(['phone' => '0999111222', 'email' => 'leaver@example.com']);
        $buyer = User::factory()->create();
        $ratedSeller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $leaver->id, 'status' => 'approved', 'title' => 'Leaver ad']);
        Favorite::create(['user_id' => $leaver->id, 'ad_id' => Ad::factory()->create(['status' => 'approved'])->id]);
        Rating::create(['rated_user_id' => $ratedSeller->id, 'rater_user_id' => $leaver->id, 'score' => 1]);
        app(RatingService::class)->recomputeForUser($ratedSeller);
        $this->assertSame(1, $ratedSeller->fresh()->rating_count);

        $conversation = Conversation::create(['ad_id' => $ad->id, 'buyer_id' => $buyer->id, 'seller_id' => $leaver->id, 'last_message_at' => now()]);
        $conversation->messages()->create(['sender_id' => $leaver->id, 'body' => 'Still available']);

        $token = $leaver->createToken('mobile')->plainTextToken;
        $this->withToken($token)->deleteJson('/api/auth/me', ['password' => 'password'])->assertOk();

        $this->assertSoftDeleted($leaver);
        $this->assertSame(AccountService::DELETED_NAME, $leaver->fresh()->name);
        $this->assertNull($leaver->fresh()->email);
        $this->assertSoftDeleted($ad);
        $this->assertSame(0, $leaver->favorites()->count());
        $this->assertSame(0, $ratedSeller->fresh()->rating_count, "the leaver's rating is removed from the average");
        $this->assertSame(0, $leaver->tokens()->count());
        $this->getJson('/api/ads')->assertJsonMissing(['title' => 'Leaver ad']);

        // The buyer's chat still opens and shows the sender as "Deleted user".
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($buyer);
        $this->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Still available')
            ->assertJsonPath('data.0.sender.name', AccountService::DELETED_NAME);
        $this->getJson('/api/conversations')->assertOk()->assertJsonPath('data.0.ad.title', 'Leaver ad');

        // The phone number and email are free for a new sign-up.
        $this->postJson('/api/auth/register', ['name' => 'Fresh', 'phone' => '0999111222', 'email' => 'leaver@example.com', 'password' => 'secret123'])
            ->assertCreated();
    }

    public function test_the_website_can_delete_the_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/delete', ['password' => 'wrong'])->assertSessionHasErrorsIn('deleteAccount', 'password');
        $this->assertNotSoftDeleted($user);

        $this->actingAs($user)->post('/profile/delete', ['password' => 'password'])->assertRedirect(route('site.home'));
        $this->assertSoftDeleted($user);
        $this->assertGuest();
    }

    public function test_the_profile_page_shows_the_new_account_sections(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertOk()
            ->assertSee('name="avatar"', false)
            ->assertSee(route('site.profile.password'), false)
            ->assertSee(route('site.profile.destroy'), false);
        $this->get('/forgot-password')->assertRedirect(); // guests only — a logged-in user is redirected
    }
}
