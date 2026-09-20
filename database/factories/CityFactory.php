<?php

namespace Database\Factories;

use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'governorate_id' => Governorate::factory(),
            'name_ar' => fake()->unique()->city(),
        ];
    }
}
