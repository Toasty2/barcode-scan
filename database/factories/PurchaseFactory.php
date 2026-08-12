<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory()->create();

        return [
            'trip_id' => Trip::factory(),
            'upc' => $product->upc,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price' => $product->price,
        ];
    }
}
