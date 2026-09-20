<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'phone' => '0999999998',
            'password' => 'password123',
        ]);

        $response->assertCreated()->assertJsonStructure(['user', 'token']);
        $this->assertDatabaseHas('users', ['phone' => '0999999998']);
    }

    public function test_a_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['phone' => '0999999997', 'password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/auth/login', [
            'phone' => '0999999997',
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['phone' => '0999999996', 'password' => bcrypt('secret123')]);

        $this->postJson('/api/auth/login', ['phone' => '0999999996', 'password' => 'wrong'])
            ->assertStatus(422);
    }
}
