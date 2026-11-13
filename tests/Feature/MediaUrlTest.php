<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Regression: photo URLs were built from APP_URL, so browsing the site on any
// other address (http://127.0.0.1:8000 vs APP_URL=http://localhost:8000, or
// http vs https) produced image links that didn't load.
class MediaUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_urls_follow_the_host_the_visitor_used(): void
    {
        config(['app.url' => 'http://localhost:8000']);
        $ad = Ad::factory()->create(['status' => 'approved', 'title' => 'Car with photo']);
        AdImage::create(['ad_id' => $ad->id, 'path' => "ads/{$ad->id}/photo.jpg", 'is_cover' => true, 'sort_order' => 0]);

        $this->getJson("http://127.0.0.1:8000/api/ads/{$ad->id}")
            ->assertOk()
            ->assertJsonPath('data.images.0.url', "http://127.0.0.1:8000/storage/ads/{$ad->id}/photo.jpg");

        $this->get('http://127.0.0.1:8000/')
            ->assertOk()
            ->assertSee("http://127.0.0.1:8000/storage/ads/{$ad->id}/photo.jpg", false);
    }
}
