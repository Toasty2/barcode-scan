<?php

namespace Database\Factories;

use App\Models\Trip;
use Brick\Money\Money;
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
            'discount' => Money::ofMinor(fake()->boolean(20) ? fake()->numberBetween(50, 300) : 0, config('money.default_currency')),
        ];
    }
}
