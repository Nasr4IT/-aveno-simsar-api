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
}
