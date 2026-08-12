<?php

namespace Database\Factories;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shopped_on' => fake()->dateTimeBetween('-3 months', 'now'),
            'discount' => fake()->boolean(20) ? fake()->randomFloat(2, 0.5, 3) : 0,
        ];
    }
}
