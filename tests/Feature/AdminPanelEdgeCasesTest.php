<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Banner;
use App\Models\Category;
use App\Models\DeviceToken;
use App\Models\Report;
use App\Models\User;
use App\Notifications\AdApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

// AdminPanelTest covers each page's happy path; these are the stale-tab,
// double-click, odd-input and revoked-access cases around them.
class AdminPanelEdgeCasesTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    // --- Ads ---------------------------------------------------------------

    public function test_the_all_tab_lists_every_status_and_keeps_it_across_pages(): void
    {
        $admin = $this->adminUser();
        $approved = Ad::factory()->create(['status' => 'approved']);
        $this->createAds(20, 'pending');

        $this->actingAs($admin)->get(route('admin.ads.index'))
            ->assertSee(route('admin.ads.index', ['status' => 'all']));

        $this->get(route('admin.ads.index', ['status' => 'all']))
            ->assertOk()
            ->assertSee('status=all&page=2')
            ->assertSee('صفحة 1 من 2');

        $this->get(route('admin.ads.index', ['status' => 'all', 'page' => 2]))->assertOk();
        $this->assertSame(21, Ad::count());
        $this->get(route('admin.ads.index', ['status' => 'approved']))->assertSee($approved->title);
    }

    public function test_pagination_renders_the_panels_own_markup_not_tailwinds(): void
    {
        $admin = $this->adminUser();
        $this->createAds(21, 'pending');

        $this->actingAs($admin)->get(route('admin.ads.index'))
            ->assertOk()
            ->assertSee('التالي')
            ->assertDontSee('w-5 h-5')
            ->assertDontSee('Showing');
    }

    public function test_an_ad_that_is_no_longer_pending_cannot_be_approved(): void
    {
        $admin = $this->adminUser();
        $sold = Ad::factory()->create(['status' => 'sold']);
        $rejected = Ad::factory()->create(['status' => 'rejected', 'rejection_reason' => 'صور غير واضحة']);

        $this->actingAs($admin)->post(route('admin.ads.approve', $sold))->assertSessionHasErrors('ad');
        $this->post(route('admin.ads.approve', $rejected))->assertSessionHasErrors('ad');

        $this->assertSame('sold', $sold->fresh()->status);
        $this->assertSame('rejected', $rejected->fresh()->status);
    }

    public function test_a_double_click_on_approve_notifies_the_owner_only_once(): void
    {
        Notification::fake();
        $admin = $this->adminUser();
        $ad = Ad::factory()->create(['status' => 'pending']);

        $this->actingAs($admin)->post(route('admin.ads.approve', $ad))->assertSessionHasNoErrors();
        $this->post(route('admin.ads.approve', $ad))->assertSessionHasErrors('ad');

        Notification::assertSentToTimes($ad->user, AdApproved::class, 1);
    }

    public function test_a_live_ad_can_be_taken_down_from_the_approved_tab(): void
    {
        $admin = $this->adminUser();
        $ad = Ad::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)->get(route('admin.ads.index', ['status' => 'approved']))
            ->assertSee(route('admin.ads.reject', $ad));

        $this->post(route('admin.ads.reject', $ad), ['reason' => 'احتيال'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $ad->fresh()->status);

        // ...but an already-rejected one isn't rejected (and notified) again.
        $this->post(route('admin.ads.reject', $ad), ['reason' => 'احتيال'])->assertSessionHasErrors('ad');
    }

    public function test_a_reported_sold_ad_can_be_taken_down_from_the_sold_tab(): void
    {
        $admin = $this->adminUser();
        $ad = Ad::factory()->create(['status' => 'sold']);

        $this->actingAs($admin)->get(route('admin.ads.index', ['status' => 'sold']))
            ->assertSee(route('admin.ads.reject', $ad));

        $this->post(route('admin.ads.reject', $ad), ['reason' => 'احتيال'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $ad->fresh()->status);
    }

    public function test_approving_still_succeeds_when_push_delivery_fails(): void
    {
        Notification::fake();
        Http::fake(['*' => Http::failedConnection()]);
        openssl_pkey_export(openssl_pkey_new(['private_key_bits' => 2048]), $privateKey);
        config(['services.firebase.credentials_json' => json_encode([
            'project_id' => 'aveno-test', 'client_email' => 'push@aveno-test.iam.gserviceaccount.com', 'private_key' => $privateKey,
        ])]);

        $admin = $this->adminUser();
        $ad = Ad::factory()->create(['status' => 'pending']);
        DeviceToken::create(['user_id' => $ad->user_id, 'token' => 'fcm-token']);

        $this->actingAs($admin)->post(route('admin.ads.approve', $ad))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $ad->fresh()->status);
        Notification::assertSentToTimes($ad->user, AdApproved::class, 1);
        Http::assertSentCount(1);
    }

    // --- Reports -----------------------------------------------------------

    public function test_the_reports_all_tab_includes_handled_reports(): void
    {
        $admin = $this->adminUser();
        Report::create([
            'reporter_id' => User::factory()->create()->id, 'reportable_type' => User::class,
            'reportable_id' => User::factory()->create()->id, 'reason' => 'spam', 'details' => 'بلاغ تمت معالجته', 'status' => 'resolved',
        ]);

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertDontSee('بلاغ تمت معالجته');
        $this->get(route('admin.reports.index', ['status' => 'all']))->assertSee('بلاغ تمت معالجته');
    }

    // --- Categories --------------------------------------------------------

    public function test_attribute_options_split_on_the_arabic_comma_and_drop_empty_entries(): void
    {
        $admin = $this->adminUser();
        $category = Category::factory()->create();

        $this->actingAs($admin)->post(route('admin.categories.attributes.store', $category), [
            'key' => 'fuel_type', 'label_ar' => 'الوقود', 'type' => 'select', 'options' => 'بنزين، ديزل، كهربائي,',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['بنزين', 'ديزل', 'كهربائي'], $category->attributes_()->sole()->options);
    }

    public function test_attribute_keys_must_be_unique_per_category_filterable_and_not_reserved(): void
    {
        $admin = $this->adminUser();
        $category = Category::factory()->create();
        $category->attributes_()->create(['key' => 'color', 'label_ar' => 'اللون', 'type' => 'text']);
        $this->actingAs($admin);

        foreach (['color', 'fuel type', 'Color', 'sort', 'q'] as $key) {
            $this->post(route('admin.categories.attributes.store', $category), [
                'key' => $key, 'label_ar' => 'x', 'type' => 'text',
            ])->assertSessionHasErrors('key');
        }

        $this->assertSame(1, $category->attributes_()->count());
    }

    public function test_a_choice_attribute_needs_at_least_one_option(): void
    {
        $admin = $this->adminUser();
        $category = Category::factory()->create();
        $this->actingAs($admin);

        $this->post(route('admin.categories.attributes.store', $category), [
            'key' => 'material', 'label_ar' => 'الخامة', 'type' => 'select', 'options' => ' ، ',
        ])->assertSessionHasErrors('options');

        // No options field at all used to crash with "Undefined array key".
        $this->post(route('admin.categories.attributes.store', $category), [
            'key' => 'material', 'label_ar' => 'الخامة', 'type' => 'multiselect',
        ])->assertSessionHasErrors('options');

        $this->post(route('admin.categories.attributes.store', $category), [
            'key' => 'notes', 'label_ar' => 'ملاحظات', 'type' => 'text', 'options' => 'ignored',
        ])->assertSessionHasNoErrors();

        $this->assertNull($category->attributes_()->sole()->options);
    }

    public function test_is_filterable_defaults_to_true_like_the_api_and_can_be_switched_off(): void
    {
        $admin = $this->adminUser();
        $category = Category::factory()->create();
        $this->actingAs($admin);

        $this->post(route('admin.categories.attributes.store', $category), [
            'key' => 'material', 'label_ar' => 'الخامة', 'type' => 'text',
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.categories.attributes.store', $category), [
            'key' => 'notes', 'label_ar' => 'ملاحظات', 'type' => 'text', 'is_filterable' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($category->attributes_()->where('key', 'material')->sole()->is_filterable);
        $this->assertFalse($category->attributes_()->where('key', 'notes')->sole()->is_filterable);
    }

    public function test_a_category_can_be_given_a_sort_order(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('admin.categories.store'), ['name_ar' => 'أثاث', 'sort_order' => 3])
            ->assertSessionHasNoErrors();

        $category = Category::where('name_ar', 'أثاث')->sole();
        $this->assertSame(3, $category->sort_order);
        $this->assertNotEmpty($category->slug);

        // An emptied field falls back to 0 instead of inserting NULL.
        $this->post(route('admin.categories.store'), ['name_ar' => 'ملابس', 'sort_order' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame(0, Category::where('name_ar', 'ملابس')->sole()->sort_order);
    }

    // --- Banners -----------------------------------------------------------

    public function test_pausing_a_banner_twice_leaves_it_paused(): void
    {
        $admin = $this->adminUser();
        $banner = Banner::create([
            'image_path' => 'banners/a.jpg', 'starts_at' => now(), 'ends_at' => now()->addDays(10), 'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.banners.active', $banner), ['is_active' => 0]);
        $this->post(route('admin.banners.active', $banner), ['is_active' => 0]);

        $this->assertFalse($banner->fresh()->is_active);
    }

    // --- Users -------------------------------------------------------------

    public function test_banning_a_user_also_stops_their_push_notifications(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create();
        DeviceToken::create(['user_id' => $target->id, 'token' => 'fcm-token']);

        $this->actingAs($admin)->post(route('admin.users.ban', $target))->assertSessionHasNoErrors();

        $this->assertSame(0, $target->deviceTokens()->count());
    }

    // --- Login & session ---------------------------------------------------

    public function test_login_is_rate_limited_per_phone(): void
    {
        $admin = $this->adminUser();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.attempt'), ['phone' => $admin->phone, 'password' => 'wrong']);
        }

        // Even the right password is refused until the lockout expires.
        $this->post(route('admin.login.attempt'), ['phone' => $admin->phone, 'password' => 'password'])
            ->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_a_session_whose_admin_role_was_revoked_is_logged_out_instead_of_trapped(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);
        $admin->removeRole('admin');

        $this->get(route('admin.login'))->assertOk()->assertSee('تسجيل دخول المسؤولين فقط');
        $this->assertGuest();
    }

    public function test_banning_an_admin_in_the_database_ends_their_open_panel_session(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $admin->forceFill(['is_banned' => true])->save();

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_a_banned_admin_is_refused_by_the_admin_api(): void
    {
        $admin = $this->adminUser();
        $admin->forceFill(['is_banned' => true])->save();

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/ads')->assertForbidden();
    }

    public function test_the_panel_session_does_not_authenticate_api_requests(): void
    {
        $this->actingAs($this->adminUser())->get(route('admin.dashboard'))->assertOk();

        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_the_session_cookie_is_secure_on_https_requests(): void
    {
        $response = $this->withHeader('X-Forwarded-Proto', 'https')->get(route('admin.login'));

        $this->assertTrue($this->sessionCookie($response)->isSecure());
    }

    public function test_the_session_cookie_still_works_over_plain_http_for_local_dev(): void
    {
        $this->assertFalse($this->sessionCookie($this->get(route('admin.login')))->isSecure());
    }

    public function test_urls_follow_the_proxys_https_scheme(): void
    {
        $this->withHeader('X-Forwarded-Proto', 'https')
            ->get(route('admin.login'))
            ->assertSee('action="https://', false);
    }

    private function sessionCookie(TestResponse $response): Cookie
    {
        return collect($response->headers->getCookies())->first(fn (Cookie $c) => $c->getName() === config('session.cookie'));
    }

    // The factory makes a fresh city per ad, and CityFactory's unique names
    // run out well before a second page's worth — so share one set.
    private function createAds(int $count, string $status): void
    {
        $first = Ad::factory()->create(['status' => $status]);

        Ad::factory()->count($count - 1)->create([
            'status' => $status,
            ...$first->only('user_id', 'category_id', 'governorate_id', 'city_id'),
        ]);
    }
}
