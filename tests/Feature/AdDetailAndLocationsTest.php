<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\City;
use App\Models\Governorate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdDetailAndLocationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ad_detail_includes_the_description(): void
    {
        $ad = Ad::factory()->create(['status' => 'approved', 'description' => 'وصف تجريبي للإعلان']);

        $this->getJson("/api/ads/{$ad->id}")
            ->assertOk()
            ->assertJsonPath('data.description', 'وصف تجريبي للإعلان');
    }

    public function test_the_feed_still_omits_the_description(): void
    {
        Ad::factory()->create(['status' => 'approved', 'description' => 'hidden in list']);

        $this->getJson('/api/ads')
            ->assertOk()
            ->assertJsonMissingPath('data.0.description');
    }

    public function test_governorates_list_active_ones_with_active_cities(): void
    {
        $damascus = Governorate::create(['name_ar' => 'دمشق']);
        City::create(['governorate_id' => $damascus->id, 'name_ar' => 'المزة']);
        City::create(['governorate_id' => $damascus->id, 'name_ar' => 'مدينة معطلة', 'is_active' => false]);
        Governorate::create(['name_ar' => 'محافظة معطلة', 'is_active' => false]);

        $response = $this->getJson('/api/governorates')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.name_ar', 'دمشق')
            ->assertJsonCount(1, 'data.0.cities')
            ->assertJsonPath('data.0.cities.0.name_ar', 'المزة');
    }
}
