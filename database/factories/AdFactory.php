<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\City;
use App\Models\Governorate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AdFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'governorate_id' => Governorate::factory(),
            'city_id' => City::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 50, 50000),
            'status' => 'approved',
            'published_at' => now(),
        ];
    }
}
