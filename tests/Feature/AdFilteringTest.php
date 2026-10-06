<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdAttributeValue;
use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_can_be_filtered_by_category(): void
    {
        $carsCategory = Category::factory()->create();
        $realEstateCategory = Category::factory()->create();
        Ad::factory()->create(['category_id' => $carsCategory->id, 'status' => 'approved']);
        Ad::factory()->create(['category_id' => $realEstateCategory->id, 'status' => 'approved']);

        $response = $this->getJson("/api/ads?category_id={$carsCategory->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_feed_can_be_filtered_by_price_range(): void
    {
        Ad::factory()->create(['price' => 1000, 'status' => 'approved']);
        $expensive = Ad::factory()->create(['price' => 90000, 'status' => 'approved']);

        $response = $this->getJson('/api/ads?min_price=50000');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($expensive->id, $response->json('data.0.id'));
    }

    public function test_feed_can_be_searched_by_title_or_description(): void
    {
        Ad::factory()->create(['title' => 'Toyota Corolla 2018', 'status' => 'approved']);
        Ad::factory()->create(['title' => 'Apartment for rent', 'description' => 'near Toyota showroom', 'status' => 'approved']);
        Ad::factory()->create(['title' => 'Unrelated listing', 'status' => 'approved']);

        $response = $this->getJson('/api/ads?q=Toyota');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_feed_can_be_filtered_by_a_dynamic_category_attribute(): void
    {
        $this->seed(CategorySeeder::class);

        $carsCategory = Category::where('slug', 'cars')->firstOrFail();
        $conditionAttr = $carsCategory->attributes_()->where('key', 'condition')->firstOrFail();

        $used = Ad::factory()->create(['category_id' => $carsCategory->id, 'status' => 'approved']);
        $new = Ad::factory()->create(['category_id' => $carsCategory->id, 'status' => 'approved']);

        AdAttributeValue::create(['ad_id' => $used->id, 'category_attribute_id' => $conditionAttr->id, 'value' => 'مستعملة']);
        AdAttributeValue::create(['ad_id' => $new->id, 'category_attribute_id' => $conditionAttr->id, 'value' => 'جديدة']);

        $response = $this->getJson('/api/ads?'.http_build_query(['condition' => 'مستعملة']));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($used->id, $response->json('data.0.id'));
    }

    // Regression test: "condition" is defined as a filterable attribute on
    // both the cars and motorcycles categories (see CategorySeeder). Without
    // a category_id, AdController::applyAttributeFilters must OR the
    // matching attribute rows across categories, not AND them — otherwise no
    // single ad (which belongs to exactly one category) could ever satisfy
    // both category_attribute_id constraints, silently returning zero
    // results. This bug shipped once already; keep it from coming back.
    public function test_dynamic_attribute_filter_matches_across_multiple_categories_sharing_the_same_key(): void
    {
        $this->seed(CategorySeeder::class);

        $cars = Category::where('slug', 'cars')->firstOrFail();
        $motorcycles = Category::where('slug', 'motorcycles')->firstOrFail();
        $carsCondition = $cars->attributes_()->where('key', 'condition')->firstOrFail();
        $motoCondition = $motorcycles->attributes_()->where('key', 'condition')->firstOrFail();

        $usedCar = Ad::factory()->create(['category_id' => $cars->id, 'status' => 'approved']);
        $usedMoto = Ad::factory()->create(['category_id' => $motorcycles->id, 'status' => 'approved']);

        AdAttributeValue::create(['ad_id' => $usedCar->id, 'category_attribute_id' => $carsCondition->id, 'value' => 'مستعملة']);
        AdAttributeValue::create(['ad_id' => $usedMoto->id, 'category_attribute_id' => $motoCondition->id, 'value' => 'مستعملة']);

        $response = $this->getJson('/api/ads?'.http_build_query(['condition' => 'مستعملة']));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_unknown_query_parameters_are_ignored_rather_than_erroring(): void
    {
        Ad::factory()->create(['status' => 'approved']);

        $this->getJson('/api/ads?not_a_real_filter=whatever')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_feed_can_be_sorted_by_price_ascending(): void
    {
        $mid = Ad::factory()->create(['price' => 500, 'status' => 'approved']);
        $cheap = Ad::factory()->create(['price' => 100, 'status' => 'approved']);
        $expensive = Ad::factory()->create(['price' => 900, 'status' => 'approved']);

        $response = $this->getJson('/api/ads?sort=price_asc');

        $response->assertOk();
        $this->assertSame([$cheap->id, $mid->id, $expensive->id], $response->json('data.*.id'));
    }

    public function test_feed_can_be_sorted_by_price_descending(): void
    {
        $mid = Ad::factory()->create(['price' => 500, 'status' => 'approved']);
        $cheap = Ad::factory()->create(['price' => 100, 'status' => 'approved']);
        $expensive = Ad::factory()->create(['price' => 900, 'status' => 'approved']);

        $response = $this->getJson('/api/ads?sort=price_desc');

        $response->assertOk();
        $this->assertSame([$expensive->id, $mid->id, $cheap->id], $response->json('data.*.id'));
    }

    public function test_feed_can_be_sorted_by_popularity(): void
    {
        // views_count isn't mass-assignable (only ever touched via
        // Ad::show()'s increment()), so it has to be set with forceFill
        // here rather than through the factory.
        $popular = Ad::factory()->create(['status' => 'approved']);
        $popular->forceFill(['views_count' => 500])->save();
        $unpopular = Ad::factory()->create(['status' => 'approved']);
        $unpopular->forceFill(['views_count' => 2])->save();

        $response = $this->getJson('/api/ads?sort=popular');

        $response->assertOk();
        $this->assertSame([$popular->id, $unpopular->id], $response->json('data.*.id'));
    }

    public function test_feed_can_be_sorted_by_distance_from_the_caller(): void
    {
        // Seattle-ish coordinates for the caller.
        $near = Ad::factory()->create(['latitude' => 47.62, 'longitude' => -122.33, 'status' => 'approved']);
        $far = Ad::factory()->create(['latitude' => 40.71, 'longitude' => -74.01, 'status' => 'approved']); // NYC
        Ad::factory()->create(['latitude' => null, 'longitude' => null, 'status' => 'approved']); // no location at all

        $response = $this->getJson('/api/ads?sort=nearest&lat=47.60&lng=-122.33');

        $response->assertOk();
        // Only the two located ads participate; the one with no coordinates
        // can't be placed on a distance ordering at all.
        $this->assertSame([$near->id, $far->id], $response->json('data.*.id'));
    }

    public function test_sorting_by_nearest_requires_lat_and_lng(): void
    {
        Ad::factory()->create(['status' => 'approved']);

        $this->getJson('/api/ads?sort=nearest')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_search_falls_back_to_a_typo_tolerant_match_when_no_exact_match_exists(): void
    {
        $toyota = Ad::factory()->create(['title' => 'Toyota Corolla 2019', 'status' => 'approved']);
        Ad::factory()->create(['title' => 'Unrelated listing', 'status' => 'approved']);

        // No substring of "Totoya" appears anywhere, so this only succeeds
        // via the fuzzy fallback, not the plain LIKE pass.
        $response = $this->getJson('/api/ads?q=Totoya');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($toyota->id, $response->json('data.0.id'));
    }

    // Confirms the fuzzy fallback is multibyte-safe — PHP's own
    // levenshtein() works byte-by-byte and silently misbehaves on Arabic,
    // which is routine in this app's ad titles.
    public function test_search_fuzzy_fallback_works_on_arabic_text(): void
    {
        $ad = Ad::factory()->create(['title' => 'سيارة تويوتا للبيع', 'status' => 'approved']);

        $response = $this->getJson('/api/ads?'.http_build_query(['q' => 'تيوتا']));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($ad->id, $response->json('data.0.id'));
    }

    public function test_search_does_not_fuzzy_match_unrelated_words(): void
    {
        Ad::factory()->create(['title' => 'Toyota Corolla 2019', 'status' => 'approved']);

        $response = $this->getJson('/api/ads?q=Bicycle');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_an_exact_substring_match_skips_the_fuzzy_fallback_entirely(): void
    {
        // "Car" is a substring of both — if the fuzzy fallback somehow ran
        // instead of the exact pass, short common words could over-match;
        // this just confirms the exact path still wins outright.
        $exact = Ad::factory()->create(['title' => 'Nice Car for sale', 'status' => 'approved']);

        $response = $this->getJson('/api/ads?q=Car');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($exact->id, $response->json('data.0.id'));
    }

    public function test_default_sort_is_unchanged_featured_then_newest(): void
    {
        $older = Ad::factory()->create(['status' => 'approved', 'created_at' => now()->subDay()]);
        $newer = Ad::factory()->create(['status' => 'approved', 'created_at' => now()]);
        $featuredButOlder = Ad::factory()->create(['status' => 'approved', 'is_featured' => true, 'created_at' => now()->subDays(5)]);

        $response = $this->getJson('/api/ads');

        $response->assertOk();
        $this->assertSame([$featuredButOlder->id, $newer->id, $older->id], $response->json('data.*.id'));
    }
}
