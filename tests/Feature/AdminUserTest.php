<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    public function test_non_admin_cannot_ban_users(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($user);
        $this->postJson("/api/admin/users/{$target->id}/ban")->assertForbidden();
    }

    public function test_admin_can_ban_and_unban_a_user_and_the_ban_is_enforced_at_login(): void
    {
        // adminUser() must run to completion (including assignRole, which
        // resolves against Spatie's *default* guard) before the first
        // Sanctum::actingAs() call — that call switches the app's default
        // guard to "sanctum" for the rest of this test via Auth::shouldUse(),
        // so a second adminUser() call afterwards would fail to assign the
        // "admin" role (created for guard "web") against the wrong guard.
        $admin = $this->adminUser();
        $target = User::factory()->create(['phone' => '0977000111', 'password' => bcrypt('password123')]);

        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/users/{$target->id}/ban")
            ->assertOk()
            ->assertJsonPath('data.is_banned', true);

        $this->assertTrue($target->fresh()->is_banned);

        $this->postJson('/api/auth/login', ['phone' => '0977000111', 'password' => 'password123'])
            ->assertForbidden();

        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/users/{$target->id}/unban")
            ->assertOk()
            ->assertJsonPath('data.is_banned', false);

        $this->assertFalse($target->fresh()->is_banned);
    }

    public function test_admin_can_search_users_by_name_or_phone(): void
    {
        User::factory()->create(['name' => 'Ahmad Zein', 'phone' => '0955111222']);
        User::factory()->create(['name' => 'Someone Else', 'phone' => '0955333444']);

        Sanctum::actingAs($this->adminUser());
        $this->getJson('/api/admin/users?q=Ahmad')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/users?q=0955333444')->assertOk()->assertJsonCount(1, 'data');
    }

    // Regression test: banning a user used to only block future logins —
    // an already-issued token kept working on every other endpoint
    // indefinitely, since nothing revoked it. Verified via real tokens (not
    // Sanctum::actingAs(), which caches its resolved user on the guard for
    // the whole test and would mask this). Sanctum's guard is a
    // RequestGuard, which caches whichever user it resolves first for the
    // rest of the test regardless of which token a later call sends — so
    // forgetGuards() is needed before *every* switch to a different token,
    // not just once at the end.
    public function test_banning_a_user_revokes_their_existing_tokens(): void
    {
        $admin = $this->adminUser();
        $adminToken = $admin->createToken('mobile')->plainTextToken;
        $target = User::factory()->create();
        $targetToken = $target->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', "Bearer $targetToken")
            ->getJson('/api/auth/me')
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer $adminToken")
            ->postJson("/api/admin/users/{$target->id}/ban")
            ->assertOk();

        $this->assertSame(0, $target->tokens()->count());

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer $targetToken")
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_an_admin_cannot_ban_another_admin(): void
    {
        $admin = $this->adminUser();
        $otherAdmin = $this->adminUser();

        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/users/{$otherAdmin->id}/ban")->assertStatus(422);

        $this->assertFalse($otherAdmin->fresh()->is_banned);
    }
}
