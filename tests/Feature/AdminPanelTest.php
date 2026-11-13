<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get('/admin-panel')->assertRedirect(route('admin.login'));
    }

    public function test_a_non_admin_cannot_log_in_to_the_panel(): void
    {
        $user = User::factory()->create();

        $this->post('/admin-panel/login', ['phone' => $user->phone, 'password' => 'password'])
            ->assertRedirect();

        $this->assertGuest();
    }

    public function test_an_admin_can_log_in_and_see_the_dashboard(): void
    {
        $admin = $this->adminUser();

        $this->post('/admin-panel/login', ['phone' => $admin->phone, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('لوحة التحكم');
    }

    public function test_a_banned_admin_cannot_log_in(): void
    {
        $admin = $this->adminUser();
        $admin->forceFill(['is_banned' => true])->save();

        $this->post('/admin-panel/login', ['phone' => $admin->phone, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_admin_can_approve_a_pending_ad_from_the_panel(): void
    {
        $admin = $this->adminUser();
        $ad = Ad::factory()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->post(route('admin.ads.approve', $ad))
            ->assertRedirect();

        $this->assertSame('approved', $ad->fresh()->status);
    }

    public function test_admin_can_reject_a_pending_ad_with_a_reason(): void
    {
        $admin = $this->adminUser();
        $ad = Ad::factory()->create(['status' => 'pending']);

        $this->actingAs($admin)
            ->post(route('admin.ads.reject', $ad), ['reason' => 'صور غير واضحة'])
            ->assertRedirect();

        $ad->refresh();
        $this->assertSame('rejected', $ad->status);
        $this->assertSame('صور غير واضحة', $ad->rejection_reason);
    }

    public function test_a_sold_ad_cannot_be_approved_or_rejected_from_the_panel(): void
    {
        $admin = $this->adminUser();
        $ad = Ad::factory()->create(['status' => 'sold']);

        $this->actingAs($admin)->post(route('admin.ads.approve', $ad))->assertStatus(422);
        $this->actingAs($admin)->post(route('admin.ads.reject', $ad), ['reason' => 'test'])->assertStatus(422);

        $this->assertSame('sold', $ad->fresh()->status);
    }

    public function test_admin_can_ban_and_unban_a_user_from_the_panel(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.ban', $target))->assertRedirect();
        $this->assertTrue($target->fresh()->is_banned);

        $this->actingAs($admin)->post(route('admin.users.unban', $target))->assertRedirect();
        $this->assertFalse($target->fresh()->is_banned);
    }

    public function test_admin_cannot_ban_another_admin_from_the_panel(): void
    {
        $admin = $this->adminUser();
        $otherAdmin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.users.ban', $otherAdmin))->assertStatus(422);
        $this->assertFalse($otherAdmin->fresh()->is_banned);
    }

    public function test_admin_can_create_a_category_and_a_dynamic_attribute(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['name_ar' => 'أثاث'])
            ->assertRedirect();

        $category = Category::where('name_ar', 'أثاث')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.categories.attributes.store', $category), [
                'key' => 'material', 'label_ar' => 'الخامة', 'type' => 'select', 'options' => 'خشب, معدن',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('category_attributes', ['category_id' => $category->id, 'key' => 'material']);
    }

    // Regression test: the website previously had no way to edit a category
    // at all (create/delete only) despite the API supporting it — see
    // AdminCategoryController@update.
    public function test_admin_can_edit_a_category_from_the_panel(): void
    {
        $admin = $this->adminUser();
        $category = Category::factory()->create(['name_ar' => 'قديم', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.categories.update', $category), [
                'name_ar' => 'جديد', 'name_en' => 'New', 'sort_order' => 5,
                // is_active omitted — a plain checkbox left unchecked, so
                // this should deactivate the category.
            ])
            ->assertRedirect();

        $category->refresh();
        $this->assertSame('جديد', $category->name_ar);
        $this->assertSame(5, $category->sort_order);
        $this->assertFalse($category->is_active);
    }

    public function test_admin_can_create_toggle_and_delete_a_banner_from_the_panel(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.banners.store'), [
            'image' => UploadedFile::fake()->image('b.jpg'),
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDays(10)->toDateTimeString(),
        ])->assertRedirect();

        $banner = Banner::sole();
        $this->assertTrue($banner->is_active);

        $this->actingAs($admin)->post(route('admin.banners.toggle', $banner))->assertRedirect();
        $this->assertFalse($banner->fresh()->is_active);

        $this->actingAs($admin)->post(route('admin.banners.destroy', $banner))->assertRedirect();
        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    // Regression test: the website previously had no way to edit a banner's
    // fields at all (create/toggle/delete only) despite the API supporting
    // it — see AdminBannerController@update.
    public function test_admin_can_edit_a_banner_from_the_panel(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();
        $banner = Banner::create([
            'image_path' => 'banners/a.jpg', 'title' => 'Old title',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.banners.update', $banner), [
                'title' => 'New title',
                'starts_at' => now()->toDateTimeString(),
                'ends_at' => now()->addDays(5)->toDateTimeString(),
                'sort_order' => 3,
            ])
            ->assertRedirect();

        $banner->refresh();
        $this->assertSame('New title', $banner->title);
        $this->assertSame(3, $banner->sort_order);
    }

    // Regression test: AdminBannerController@update (API) used to validate
    // ends_at as just a date with no after_or_equal:starts_at check,
    // silently allowing a window that can never go active. Same bug could
    // exist on this panel's form; confirm it's actually blocked here too.
    public function test_editing_a_banner_rejects_an_end_date_before_the_start_date(): void
    {
        $admin = $this->adminUser();
        $banner = Banner::create([
            'image_path' => 'banners/a.jpg', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.banners.update', $banner), [
                'starts_at' => now()->toDateTimeString(),
                'ends_at' => now()->subDay()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('ends_at');
    }

    public function test_admin_can_resolve_and_dismiss_reports_from_the_panel(): void
    {
        $admin = $this->adminUser();
        $reporter = User::factory()->create();
        $reported = User::factory()->create();
        $report = Report::create([
            'reporter_id' => $reporter->id, 'reportable_type' => User::class, 'reportable_id' => $reported->id, 'reason' => 'spam',
        ]);

        $this->actingAs($admin)->post(route('admin.reports.resolve', $report))->assertRedirect();
        $this->assertSame('resolved', $report->fresh()->status);

        $report->update(['status' => 'pending']);
        $this->actingAs($admin)->post(route('admin.reports.dismiss', $report))->assertRedirect();
        $this->assertSame('dismissed', $report->fresh()->status);
    }

    public function test_a_logged_in_regular_user_cannot_reach_the_admin_panel(): void
    {
        $user = User::factory()->create();

        // Still kept out — but sent to the admin login instead of a dead-end 403.
        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->actingAs($user)->post(route('admin.users.ban', $user))->assertRedirect(route('admin.login'));
        $this->assertFalse($user->fresh()->is_banned);
    }

    // Regression: a member signed in on the public website (same session)
    // used to be bounced from /admin-panel/login into the panel and get a
    // bare "403 Admins only." with no way to sign in as the admin.
    public function test_a_website_member_sees_the_admin_login_form_and_can_switch_to_the_admin_account(): void
    {
        $member = User::factory()->create(['name' => 'Website Member']);
        $admin = $this->adminUser();

        $this->actingAs($member)->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Website Member')
            ->assertSee('ليس حساب مدير');

        $this->post(route('admin.login.attempt'), ['phone' => $admin->phone, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_the_admin_api_still_answers_403_json_for_a_non_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/admin/ads')->assertForbidden();
    }

    // Regression: the options hint shows the Arabic comma ("بنزين، ديزل")
    // but only the Latin comma was split, so following the hint created a
    // single option containing the whole list.
    public function test_attribute_options_split_on_the_arabic_comma_too(): void
    {
        $admin = $this->adminUser();
        $category = Category::factory()->create();

        $this->actingAs($admin)->post(route('admin.categories.attributes.store', $category), [
            'key' => 'fuel', 'label_ar' => 'الوقود', 'label_en' => 'Fuel', 'type' => 'select', 'options' => 'بنزين، ديزل ,كهربائي',
        ])->assertRedirect();

        $attr = $category->attributes_()->sole();
        $this->assertSame(['بنزين', 'ديزل', 'كهربائي'], $attr->options);
        $this->assertSame('Fuel', $attr->label_en);
    }

    // The tests above only ever POST to the mutating actions (which just
    // redirect) — none of them actually render the index Blade templates,
    // which is exactly where an undefined variable or a bad relation call
    // would blow up. Render every one at least once, with real data loaded,
    // to catch that class of bug.
    public function test_every_admin_panel_index_page_renders_without_error(): void
    {
        $admin = $this->adminUser();
        $category = Category::factory()->create();
        $ad = Ad::factory()->create(['status' => 'pending', 'category_id' => $category->id]);
        $ad->images()->create(['path' => 'ads/1/a.jpg', 'is_cover' => true, 'sort_order' => 0]);
        $category->attributes_()->create(['key' => 'color', 'label_ar' => 'اللون', 'type' => 'select', 'options' => ['أحمر', 'أزرق']]);
        Banner::create(['image_path' => 'banners/a.jpg', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10)]);
        Report::create([
            'reporter_id' => User::factory()->create()->id, 'reportable_type' => Ad::class, 'reportable_id' => $ad->id, 'reason' => 'spam',
        ]);

        $this->actingAs($admin);
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.ads.index'))->assertOk()->assertSee($ad->title);
        $this->get(route('admin.ads.index', ['status' => '']))->assertOk();
        $this->get(route('admin.users.index'))->assertOk()->assertSee($admin->name);
        $this->get(route('admin.categories.index'))->assertOk()->assertSee('اللون');
        $this->get(route('admin.banners.index'))->assertOk();
        // Shown as its readable label, not the raw "spam" code.
        $this->get(route('admin.reports.index'))->assertOk()->assertSee('إعلان مكرر / سبام');
        $this->get(route('admin.reports.index', ['status' => '']))->assertOk();
    }
}
