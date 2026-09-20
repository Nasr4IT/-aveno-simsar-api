<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    public function test_non_admin_cannot_manage_categories(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/admin/categories', ['name_ar' => 'فئة'])->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_a_category(): void
    {
        Sanctum::actingAs($this->adminUser());

        $create = $this->postJson('/api/admin/categories', ['name_ar' => 'فئة اختبار', 'name_en' => 'Test Category']);
        $create->assertSuccessful();
        $categoryId = $create->json('data.id');
        $this->assertDatabaseHas('categories', ['id' => $categoryId, 'name_ar' => 'فئة اختبار']);

        $this->putJson("/api/admin/categories/{$categoryId}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/admin/categories/{$categoryId}")->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
    }

    public function test_admin_can_add_a_dynamic_attribute_to_a_category(): void
    {
        $category = Category::factory()->create();
        Sanctum::actingAs($this->adminUser());

        $response = $this->postJson("/api/admin/categories/{$category->id}/attributes", [
            'key' => 'color',
            'label_ar' => 'اللون',
            'type' => 'select',
            'options' => ['أحمر', 'أزرق'],
            'is_required' => false,
            'is_filterable' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('category_attributes', ['category_id' => $category->id, 'key' => 'color']);
    }

    // Regression test: ads.category_id has no ON DELETE rule, so deleting a
    // category that still has ads used to crash with a raw, unhandled
    // QueryException (foreign key violation) instead of a clean error.
    public function test_deleting_a_category_with_ads_returns_a_friendly_error_instead_of_crashing(): void
    {
        $category = Category::factory()->create();
        Ad::factory()->create(['category_id' => $category->id]);

        Sanctum::actingAs($this->adminUser());
        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(409);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
