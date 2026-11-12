<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The public website's read-only pages. Filtering/search/sort are the same
// AdFeedQueryService the API uses, so these are smoke tests of the pages
// and their wiring, not a second copy of AdFilteringTest.
class SiteBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthz_still_returns_the_json_status(): void
    {
        $this->getJson('/healthz')->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_home_page_shows_active_categories_and_approved_ads_only(): void
    {
        $cars = Category::factory()->create(['name_ar' => 'سيارات']);
        Category::factory()->create(['name_ar' => 'فئة مخفية', 'is_active' => false]);
        Ad::factory()->create(['title' => 'Toyota Corolla 2019', 'status' => 'approved', 'category_id' => $cars->id]);
        Ad::factory()->create(['title' => 'Pending listing', 'status' => 'pending']);

        $this->get('/')
            ->assertOk()
            ->assertSee('سيارات')
            ->assertDontSee('فئة مخفية')
            ->assertSee('Toyota Corolla 2019')
            ->assertDontSee('Pending listing');
    }

    public function test_explore_page_filters_by_search_and_category_and_sorts(): void
    {
        $cars = Category::factory()->create(['name_ar' => 'سيارات']);
        $homes = Category::factory()->create(['name_ar' => 'عقارات']);
        Ad::factory()->create(['title' => 'Toyota Corolla', 'status' => 'approved', 'category_id' => $cars->id, 'price' => 9000]);
        Ad::factory()->create(['title' => 'Kia Rio', 'status' => 'approved', 'category_id' => $cars->id, 'price' => 4000]);
        Ad::factory()->create(['title' => 'Apartment for rent', 'status' => 'approved', 'category_id' => $homes->id, 'price' => 300]);

        $this->get('/explore?q=Toyota')
            ->assertOk()
            ->assertSee('Toyota Corolla')
            ->assertDontSee('Kia Rio');

        $this->get("/explore?category_id={$cars->id}")
            ->assertOk()
            ->assertSee('Kia Rio')
            ->assertDontSee('Apartment for rent');

        $this->get('/explore?sort=price_asc')
            ->assertOk()
            ->assertSeeInOrder(['Apartment for rent', 'Kia Rio', 'Toyota Corolla']);
    }

    public function test_explore_nearest_sort_needs_a_position_like_the_api(): void
    {
        $this->get('/explore?sort=nearest')->assertSessionHasErrors(['lat', 'lng']);
    }

    public function test_ad_detail_page_renders_and_counts_the_view(): void
    {
        $ad = Ad::factory()->create(['title' => 'Honda Civic 2018', 'status' => 'approved', 'price' => 12000]);
        $similar = Ad::factory()->create(['title' => 'Honda Accord', 'status' => 'approved', 'category_id' => $ad->category_id, 'price' => 12500]);

        $this->get(route('site.ads.show', $ad))
            ->assertOk()
            ->assertSee('Honda Civic 2018')
            ->assertSee($ad->user->name)
            ->assertSee('Honda Accord');

        $this->assertSame(1, $ad->fresh()->views_count);
    }

    public function test_a_pending_ad_is_not_publicly_viewable_but_a_sold_one_is(): void
    {
        $pending = Ad::factory()->create(['status' => 'pending']);
        $sold = Ad::factory()->create(['status' => 'sold']);

        $this->get(route('site.ads.show', $pending))->assertNotFound();
        $this->get(route('site.ads.show', $sold))->assertOk()->assertSee('مُباع');
    }
}
