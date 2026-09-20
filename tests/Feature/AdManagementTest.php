<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\City;
use App\Models\Governorate;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_ad_requires_authentication(): void
    {
        $this->postJson('/api/ads', [])->assertUnauthorized();
    }

    public function test_owner_can_create_an_ad_with_dynamic_attributes_and_an_image(): void
    {
        Storage::fake('public');
        $this->seed(CategorySeeder::class);

        $user = User::factory()->create();
        $category = Category::where('slug', 'cars')->firstOrFail();
        $governorate = Governorate::factory()->create();
        $city = City::factory()->create(['governorate_id' => $governorate->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ads', [
            'category_id' => $category->id,
            'governorate_id' => $governorate->id,
            'city_id' => $city->id,
            'title' => 'Kia Sportage 2020',
            'description' => 'Well maintained, single owner.',
            'price' => 15000,
            'currency' => 'USD',
            'attributes' => ['condition' => 'مستعملة', 'year' => 2020],
            'images' => [UploadedFile::fake()->image('car.jpg', 800, 600)],
        ]);

        $response->assertCreated();
        $this->assertSame('pending', $response->json('data.status'));
        $this->assertDatabaseHas('ads', ['title' => 'Kia Sportage 2020', 'user_id' => $user->id, 'status' => 'pending']);
        $this->assertDatabaseHas('ad_attribute_values', ['value' => '2020']);
        $this->assertCount(1, $response->json('data.images'));
    }

    public function test_creating_an_ad_fails_validation_when_a_required_dynamic_attribute_is_missing(): void
    {
        Storage::fake('public');
        $this->seed(CategorySeeder::class);

        $user = User::factory()->create();
        $category = Category::where('slug', 'cars')->firstOrFail();
        $governorate = Governorate::factory()->create();
        $city = City::factory()->create(['governorate_id' => $governorate->id]);

        Sanctum::actingAs($user);

        // "condition" and "year" are both required on the cars category —
        // only "condition" is sent here.
        $response = $this->postJson('/api/ads', [
            'category_id' => $category->id,
            'governorate_id' => $governorate->id,
            'city_id' => $city->id,
            'title' => 'Incomplete Ad',
            'description' => 'Missing the year attribute.',
            'attributes' => ['condition' => 'مستعملة'],
            'images' => [UploadedFile::fake()->image('car.jpg')],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('attributes');
    }

    // Regression test: Laravel's 'image' validation rule accepts formats GD
    // can't decode (e.g. SVG) — ImageService used to let that crash through
    // as an unhandled 500 instead of a normal validation error.
    public function test_an_undecodable_image_upload_fails_validation_instead_of_crashing(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = Category::factory()->create();
        $governorate = Governorate::factory()->create();
        $city = City::factory()->create(['governorate_id' => $governorate->id]);

        Sanctum::actingAs($user);

        $svg = UploadedFile::fake()->createWithContent(
            'evil.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        );

        $response = $this->postJson('/api/ads', [
            'category_id' => $category->id,
            'governorate_id' => $governorate->id,
            'city_id' => $city->id,
            'title' => 'SVG test',
            'description' => 'test',
            'images' => [$svg],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('images');
    }

    public function test_only_the_owner_can_update_their_ad(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);

        Sanctum::actingAs($stranger);
        $this->putJson("/api/ads/{$ad->id}", ['title' => 'Hijacked'])->assertForbidden();

        Sanctum::actingAs($owner);
        $this->putJson("/api/ads/{$ad->id}", ['title' => 'Updated title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated title');
    }

    public function test_updating_an_ad_resets_its_status_to_pending_for_re_review(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);

        Sanctum::actingAs($owner);
        $this->putJson("/api/ads/{$ad->id}", ['title' => 'Edited'])->assertOk();

        $this->assertSame('pending', $ad->fresh()->status);
    }

    public function test_only_the_owner_can_delete_their_ad(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);

        Sanctum::actingAs($stranger);
        $this->deleteJson("/api/ads/{$ad->id}")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/ads/{$ad->id}")->assertOk();

        $this->assertSoftDeleted($ad);
    }

    public function test_my_ads_returns_only_the_authenticated_users_ads_regardless_of_status(): void
    {
        $user = User::factory()->create();
        Ad::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
        Ad::factory()->create(['user_id' => $user->id, 'status' => 'rejected']);
        Ad::factory()->create(['status' => 'approved']); // someone else's ad

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/my/ads');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_owner_can_add_photos_to_an_existing_ad(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);
        $ad->images()->create(['path' => 'ads/1/a.jpg', 'is_cover' => true, 'sort_order' => 0]);

        Sanctum::actingAs($owner);
        $response = $this->postJson("/api/ads/{$ad->id}/images", [
            'images' => [UploadedFile::fake()->image('b.jpg', 800, 600)],
        ]);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.images'));
        // Adding a photo is an edit, so it goes back to pending like any
        // other change to an already-reviewed ad.
        $this->assertSame('pending', $ad->fresh()->status);
    }

    public function test_a_stranger_cannot_add_photos_to_someone_elses_ad(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);

        Sanctum::actingAs($stranger);
        $this->postJson("/api/ads/{$ad->id}/images", [
            'images' => [UploadedFile::fake()->image('b.jpg')],
        ])->assertForbidden();
    }

    public function test_adding_photos_beyond_the_maximum_of_twelve_is_rejected(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);
        for ($i = 0; $i < 11; $i++) {
            $ad->images()->create(['path' => "ads/1/{$i}.jpg", 'is_cover' => $i === 0, 'sort_order' => $i]);
        }

        Sanctum::actingAs($owner);
        $this->postJson("/api/ads/{$ad->id}/images", [
            'images' => [UploadedFile::fake()->image('x.jpg'), UploadedFile::fake()->image('y.jpg')],
        ])->assertUnprocessable();
    }

    public function test_owner_can_remove_a_photo_and_a_new_cover_is_promoted_if_needed(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);
        $cover = $ad->images()->create(['path' => 'ads/1/a.jpg', 'is_cover' => true, 'sort_order' => 0]);
        $other = $ad->images()->create(['path' => 'ads/1/b.jpg', 'is_cover' => false, 'sort_order' => 1]);

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/ads/{$ad->id}/images/{$cover->id}")->assertOk();

        $this->assertDatabaseMissing('ad_images', ['id' => $cover->id]);
        $this->assertTrue($other->fresh()->is_cover);
        $this->assertSame('pending', $ad->fresh()->status);
    }

    public function test_the_last_remaining_photo_on_an_ad_cannot_be_removed(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);
        $image = $ad->images()->create(['path' => 'ads/1/a.jpg', 'is_cover' => true, 'sort_order' => 0]);

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/ads/{$ad->id}/images/{$image->id}")->assertStatus(422);

        $this->assertDatabaseHas('ad_images', ['id' => $image->id]);
    }

    public function test_a_stranger_cannot_remove_a_photo_from_someone_elses_ad(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);
        $image = $ad->images()->create(['path' => 'ads/1/a.jpg', 'is_cover' => true, 'sort_order' => 0]);
        $ad->images()->create(['path' => 'ads/1/b.jpg', 'is_cover' => false, 'sort_order' => 1]);

        Sanctum::actingAs($stranger);
        $this->deleteJson("/api/ads/{$ad->id}/images/{$image->id}")->assertForbidden();
    }
}
