<?php

namespace Database\Factories;

use App\Models\Product;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'upc' => fake()->unique()->numerify('#############'),
            'product_name' => fake()->randomElement([
                'Tesco British Semi Skimmed Milk 2 pints',
                'Tesco Bananas Loose',
                'Tesco Finest Sourdough Bloomer',
                'Lurpak Butter 400g',
                'Kellogg\'s Variety Cereal 8 pack',
                'Robinsons Dbl Con Sum Fruits 1.75ltr',
                'Coca Cola 1.75L',
                'Tesco Brown Onion Loose',
                'Mattessons Smoked Pork Sausage 260g',
                'Rombouts Coffee',
            ]),
            'price' => Money::ofMinor(fake()->numberBetween(50, 600), config('money.default_currency')),
            'last_confirmed' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
