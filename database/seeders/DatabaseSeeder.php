<?php

namespace Database\Seeders;

use App\Models\BudgetChange;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Deliberately a small, illustrative dataset — just enough to build and
     * verify the dashboard's structure against, not a realistic simulation.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $products = Product::factory()->count(8)->create();

        $shops = [
            Shop::create(['name' => 'Tesco', 'colour' => '#00539F', 'is_default' => true]),
            Shop::create(['name' => 'Waitrose', 'colour' => '#00603A', 'is_default' => false]),
            null, // some trips have no shop set, to prove the dashboard handles that
        ];

        // Two budget changes, so the dashboard has to prove it resolves the
        // right one per month rather than always using a single value.
        BudgetChange::create(['amount' => 20000, 'effective_from' => Carbon::now()->subMonths(2)->startOfMonth()]);
        BudgetChange::create(['amount' => 25000, 'effective_from' => Carbon::now()->startOfMonth()]);

        foreach ([2, 1, 0] as $monthsAgo) {
            $tripsThisMonth = $monthsAgo === 0 ? 2 : 3;

            for ($i = 0; $i < $tripsThisMonth; $i++) {
                $shoppedOn = Carbon::now()->subMonths($monthsAgo)->startOfMonth()->addDays(fake()->numberBetween(0, 27));

                $trip = Trip::create([
                    'shopped_on' => $shoppedOn,
                    'shop_id' => fake()->randomElement($shops)?->id,
                    'discount' => fake()->boolean(25) ? fake()->numberBetween(50, 300) : 0,
                ]);

                foreach ($products->random(fake()->numberBetween(4, 6)) as $product) {
                    $trip->purchases()->create([
                        'upc' => $product->upc,
                        'product_name' => $product->product_name,
                        'entry_type' => 'scan',
                        'quantity' => fake()->numberBetween(1, 3),
                        'unit_price' => $product->price,
                    ]);
                }
            }
        }
    }
}
