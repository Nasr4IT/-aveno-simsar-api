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
use Tests\TestCase;

// Posting and managing one's own ads from the website — the browser
// counterpart of AdManagementTest, with the same rules (same
// StoreAdRequest/UpdateAdRequest and AdAttributeService as the API).
class SiteSellingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_sent_to_the_site_login_to_sell(): void
    {
        $this->get('/sell')->assertRedirect(route('site.login'));
        $this->get('/my/ads')->assertRedirect(route('site.login'));
    }

    public function test_the_sell_page_shows_a_categorys_dynamic_fields_once_chosen(): void
    {
        $this->seed(CategorySeeder::class);
        $cars = Category::where('slug', 'cars')->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get('/sell?category_id='.$cars->id)
            ->assertOk()
            ->assertSee('attributes[condition]', false)
            ->assertSee('attributes[year]', false);
    }

    public function test_a_user_can_post_an_ad_with_dynamic_attributes_and_a_photo(): void
    {
        Storage::fake('public');
        $this->seed(CategorySeeder::class);
        $user = User::factory()->create();
        $cars = Category::where('slug', 'cars')->firstOrFail();
        $governorate = Governorate::factory()->create();
        $city = City::factory()->create(['governorate_id' => $governorate->id]);

        $this->actingAs($user)->post('/sell', [
            'category_id' => $cars->id,
            'governorate_id' => $governorate->id,
            'city_id' => $city->id,
            'title' => 'Kia Sportage 2020',
            'description' => 'Well maintained, single owner.',
            'price' => 15000,
            'currency' => 'USD',
            'attributes' => ['condition' => 'مستعملة', 'year' => 2020],
            'images' => [UploadedFile::fake()->image('car.jpg', 800, 600)],
        ])->assertRedirect(route('site.my.ads'));

        $ad = Ad::where('title', 'Kia Sportage 2020')->firstOrFail();
        $this->assertSame('pending', $ad->status);
        $this->assertSame($user->id, $ad->user_id);
        $this->assertDatabaseHas('ad_attribute_values', ['ad_id' => $ad->id, 'value' => '2020']);
        $this->assertCount(1, $ad->images);
        $this->assertTrue($ad->images->first()->is_cover);
    }

    public function test_posting_fails_when_a_required_dynamic_attribute_is_missing(): void
    {
        Storage::fake('public');
        $this->seed(CategorySeeder::class);
        $cars = Category::where('slug', 'cars')->firstOrFail();
        $governorate = Governorate::factory()->create();
        $city = City::factory()->create(['governorate_id' => $governorate->id]);

        $this->actingAs(User::factory()->create())->post('/sell', [
            'category_id' => $cars->id,
            'governorate_id' => $governorate->id,
            'city_id' => $city->id,
            'title' => 'Incomplete',
            'description' => 'Missing the year.',
            'attributes' => ['condition' => 'مستعملة'],
            'images' => [UploadedFile::fake()->image('car.jpg')],
        ])->assertSessionHasErrors('attributes');

        $this->assertDatabaseMissing('ads', ['title' => 'Incomplete']);
    }

    public function test_my_ads_lists_only_the_users_own_ads_with_stats(): void
    {
        $user = User::factory()->create();
        $mine = Ad::factory()->create(['user_id' => $user->id, 'status' => 'approved', 'title' => 'My approved ad']);
        $mine->forceFill(['views_count' => 40])->save();
        Ad::factory()->create(['user_id' => $user->id, 'status' => 'rejected', 'title' => 'My rejected ad', 'rejection_reason' => 'صور غير واضحة']);
        Ad::factory()->create(['status' => 'approved', 'title' => 'Someone elses ad']);

        $this->actingAs($user)->get('/my/ads')
            ->assertOk()
            ->assertSee('My approved ad')
            ->assertSee('My rejected ad')
            ->assertSee('صور غير واضحة')
            ->assertDontSee('Someone elses ad')
            ->assertSee('40');
    }

    public function test_editing_an_ad_resets_it_to_pending(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);

        $this->actingAs($owner)->get(route('site.my.ads.edit', $ad))->assertOk();

        $this->actingAs($owner)->post(route('site.my.ads.update', $ad), [
            'title' => 'Edited title', 'description' => $ad->description, 'price' => 123,
        ])->assertRedirect(route('site.my.ads'));

        $ad->refresh();
        $this->assertSame('Edited title', $ad->title);
        $this->assertSame('pending', $ad->status);
    }

    public function test_a_stranger_cannot_edit_someone_elses_ad(): void
    {
        $ad = Ad::factory()->create(['status' => 'approved']);

        $this->actingAs(User::factory()->create())
            ->post(route('site.my.ads.update', $ad), ['title' => 'Hijacked'])
            ->assertForbidden();
    }

    public function test_a_sold_ad_cannot_be_edited_until_relisted(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'sold']);

        $this->actingAs($owner)->get(route('site.my.ads.edit', $ad))
            ->assertRedirect(route('site.my.ads'))
            ->assertSessionHasErrors('ad');

        $this->actingAs($owner)->post(route('site.my.ads.update', $ad), ['title' => 'Still sold?'])
            ->assertSessionHasErrors('ad');

        $this->assertSame('sold', $ad->fresh()->status);
    }

    public function test_owner_can_mark_sold_relist_and_delete(): void
    {
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);

        $this->actingAs($owner)->post(route('site.my.ads.mark-sold', $ad))->assertRedirect();
        $this->assertSame('sold', $ad->fresh()->status);

        $this->actingAs($owner)->post(route('site.my.ads.relist', $ad))->assertRedirect();
        $this->assertSame('pending', $ad->fresh()->status);

        // Relisting an ad that isn't sold/expired is refused.
        $this->actingAs($owner)->post(route('site.my.ads.relist', $ad))->assertSessionHasErrors('ad');

        $this->actingAs($owner)->post(route('site.my.ads.destroy', $ad))->assertRedirect(route('site.my.ads'));
        $this->assertSoftDeleted($ad);
    }

    public function test_owner_can_add_and_remove_photos_but_not_the_last_one(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $owner->id, 'status' => 'approved']);
        $cover = $ad->images()->create(['path' => 'ads/1/a.jpg', 'is_cover' => true, 'sort_order' => 0]);

        $this->actingAs($owner)->post(route('site.my.ads.images.store', $ad), [
            'images' => [UploadedFile::fake()->image('b.jpg', 800, 600)],
        ])->assertRedirect();
        $this->assertCount(2, $ad->fresh()->images);
        $this->assertSame('pending', $ad->fresh()->status);

        $this->actingAs($owner)->post(route('site.my.ads.images.destroy', [$ad, $cover]))->assertRedirect();
        $this->assertDatabaseMissing('ad_images', ['id' => $cover->id]);
        $this->assertTrue($ad->fresh()->images->first()->is_cover);

        $last = $ad->fresh()->images->first();
        $this->actingAs($owner)->post(route('site.my.ads.images.destroy', [$ad, $last]))->assertSessionHasErrors('images');
        $this->assertDatabaseHas('ad_images', ['id' => $last->id]);
    }
}
