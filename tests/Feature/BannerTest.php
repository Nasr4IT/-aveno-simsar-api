<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    public function test_public_banners_endpoint_only_returns_currently_active_ones(): void
    {
        $current = Banner::create([
            'image_path' => 'banners/current.jpg', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10),
        ]);
        Banner::create([
            'image_path' => 'banners/expired.jpg', 'starts_at' => now()->subDays(20), 'ends_at' => now()->subDay(),
        ]);
        Banner::create([
            'image_path' => 'banners/future.jpg', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(10),
        ]);
        Banner::create([
            'image_path' => 'banners/paused.jpg', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10), 'is_active' => false,
        ]);

        $response = $this->getJson('/api/banners');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($current->id, $response->json('data.0.id'));
    }

    public function test_banners_are_ordered_by_sort_order(): void
    {
        $second = Banner::create([
            'image_path' => 'banners/b.jpg', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'sort_order' => 2,
        ]);
        $first = Banner::create([
            'image_path' => 'banners/a.jpg', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/banners');

        $response->assertOk();
        $this->assertSame([$first->id, $second->id], $response->json('data.*.id'));
    }

    public function test_non_admin_cannot_manage_banners(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/banners')->assertForbidden();
        $this->postJson('/api/admin/banners', [])->assertForbidden();
    }

    public function test_admin_can_create_a_banner(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->adminUser());

        $response = $this->postJson('/api/admin/banners', [
            'image' => UploadedFile::fake()->image('ad.jpg', 1200, 400),
            'title' => 'Pizza Palace',
            'link_url' => 'tel:+963911111111',
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDays(30)->toDateTimeString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Pizza Palace')
            ->assertJsonPath('data.link_url', 'tel:+963911111111');
        $this->assertDatabaseCount('banners', 1);
    }

    public function test_banner_link_url_must_be_a_web_link_or_phone_number(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->adminUser());

        $this->postJson('/api/admin/banners', [
            'image' => UploadedFile::fake()->image('ad.jpg'),
            'link_url' => 'javascript:alert(1)',
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDays(30)->toDateTimeString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('link_url');
    }

    public function test_banner_end_date_cannot_be_before_its_start_date(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->adminUser());

        $this->postJson('/api/admin/banners', [
            'image' => UploadedFile::fake()->image('ad.jpg'),
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->subDay()->toDateTimeString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('ends_at');
    }

    public function test_admin_can_pause_a_banner_early_without_changing_its_dates(): void
    {
        $banner = Banner::create([
            'image_path' => 'banners/a.jpg', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10),
        ]);
        Sanctum::actingAs($this->adminUser());

        $this->putJson("/api/admin/banners/{$banner->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertSame(
            $banner->ends_at->toDateTimeString(),
            $banner->fresh()->ends_at->toDateTimeString()
        );
    }

    public function test_admin_can_delete_a_banner_and_its_image_is_removed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/a.jpg', 'fake-content');
        $banner = Banner::create([
            'image_path' => 'banners/a.jpg', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10),
        ]);
        Sanctum::actingAs($this->adminUser());

        $this->deleteJson("/api/admin/banners/{$banner->id}")->assertOk();

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing('banners/a.jpg');
    }
}
