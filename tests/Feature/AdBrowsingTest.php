<?php

namespace Tests\Feature;

use App\Models\Ad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_feed_only_returns_approved_ads(): void
    {
        Ad::factory()->count(2)->create(['status' => 'approved']);
        Ad::factory()->create(['status' => 'pending']);

        $response = $this->getJson('/api/ads');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }
}
