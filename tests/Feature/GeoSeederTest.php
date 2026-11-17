<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Governorate;
use Database\Seeders\GeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The location list must be complete (was 2–3 starter cities per
// governorate) and re-running the seeder on the live database must never
// duplicate, rename or re-number places that ads and accounts point to.
class GeoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_governorate_has_a_full_city_list_with_english_names(): void
    {
        $this->seed(GeoSeeder::class);

        $this->assertSame(14, Governorate::count());
        foreach (Governorate::withCount('cities')->get() as $governorate) {
            $this->assertGreaterThanOrEqual(10, $governorate->cities_count, $governorate->name_ar);
            $this->assertNotEmpty($governorate->name_en);
        }
        $this->assertSame(0, City::whereNull('name_en')->orWhere('name_en', '')->count());
        $this->assertGreaterThan(30, Governorate::where('name_ar', 'حماة')->first()->cities()->count());
    }

    public function test_reseeding_an_existing_database_only_adds_missing_places(): void
    {
        // The old starter data: Arabic only, a couple of cities.
        $hama = Governorate::create(['name_ar' => 'حماة']);
        $masyaf = City::create(['governorate_id' => $hama->id, 'name_ar' => 'مصياف']);

        $this->seed(GeoSeeder::class);
        $countAfterFirst = City::count();
        $this->seed(GeoSeeder::class);

        $this->assertSame($countAfterFirst, City::count(), 'second run adds nothing');
        $this->assertSame(1, Governorate::where('name_ar', 'حماة')->count());
        $this->assertSame(1, City::where('governorate_id', $hama->id)->where('name_ar', 'مصياف')->count());
        $this->assertSame('Masyaf', $masyaf->fresh()->name_en, 'blank English name filled in, same id');
        $this->assertSame('Hama', $hama->fresh()->name_en);
    }

    public function test_the_api_returns_the_full_list(): void
    {
        $this->seed(GeoSeeder::class);

        $hama = collect($this->getJson('/api/governorates')->assertOk()->json('data'))->firstWhere('name_ar', 'حماة');
        $this->assertGreaterThan(30, count($hama['cities']));
    }
}
