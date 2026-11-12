<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Session-based login/registration for the public website. Same users
// table and credentials as the app's token-based API auth.
class SiteAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_register_pages_render_for_guests(): void
    {
        $this->get('/login')->assertOk()->assertSee('تسجيل الدخول');
        $this->get('/register')->assertOk()->assertSee('إنشاء حساب');
    }

    public function test_a_visitor_can_register_and_is_logged_in(): void
    {
        $this->post('/register', [
            'name' => 'سارة',
            'phone' => '0911111111',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect(route('site.home'));

        $user = User::where('phone', '0911111111')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('سارة', $user->name);
    }

    public function test_registration_requires_a_unique_phone_and_a_confirmed_password(): void
    {
        User::factory()->create(['phone' => '0922222222']);

        $this->post('/register', [
            'name' => 'x', 'phone' => '0922222222', 'password' => 'secret-pass-1', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['phone', 'password']);

        $this->assertGuest();
    }

    public function test_a_user_can_log_in_with_their_app_credentials(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['phone' => $user->phone, 'password' => 'password'])
            ->assertRedirect(route('site.home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_a_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['phone' => $user->phone, 'password' => 'nope'])
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_a_banned_user_cannot_log_in(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_banned' => true])->save();

        $this->post('/login', ['phone' => $user->phone, 'password' => 'password'])
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_a_logged_in_user_is_sent_home_from_the_login_page_and_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect('/');

        $this->actingAs($user)->post('/logout')->assertRedirect(route('site.home'));
        $this->assertGuest();
    }

    public function test_a_guest_hitting_a_members_only_site_route_goes_to_the_site_login_not_the_admin_one(): void
    {
        $this->post('/logout')->assertRedirect(route('site.login'));
        $this->get('/admin-panel')->assertRedirect(route('admin.login'));
    }
}
