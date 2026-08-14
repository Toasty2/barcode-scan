<?php

namespace Database\Seeders;

use App\Models\BudgetChange;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Trip;
use App\Models\User;
use Brick\Money\Money;
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
        $currency = config('money.default_currency');

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $products = Product::factory()->count(8)->create();

        // A couple of example succession chains ("this product replaced
        // that one" — e.g. a shrunk successor pack), so the combined
        // price-history behaviour has something real to demonstrate.
        $products[0]->update(['replaces_product_id' => $products[1]->id]);
        $products[2]->update(['replaces_product_id' => $products[3]->id]);

        $shops = [
            Shop::create(['name' => 'Tesco', 'colour' => '#00539F', 'is_default' => true]),
            Shop::create(['name' => 'Waitrose', 'colour' => '#00603A', 'is_default' => false]),
            null, // some trips have no shop set, to prove the dashboard handles that
        ];

        // Two budget changes, so the dashboard has to prove it resolves the
        // right one per month rather than always using a single value.
        BudgetChange::create(['amount' => Money::ofMinor(20000, $currency), 'effective_from' => Carbon::now()->subMonths(2)->startOfMonth()]);
        BudgetChange::create(['amount' => Money::ofMinor(25000, $currency), 'effective_from' => Carbon::now()->startOfMonth()]);

        // Each product drifts up from some lower price 3 years ago to
        // exactly its current cached price today (plus a little noise per
        // purchase), so the price-history chart has something real to show
        // instead of every purchase pinning the same flat cached price.
        $priceDriftFactors = $products->mapWithKeys(
            fn (Product $product) => [$product->upc => fake()->randomFloat(2, 0.05, 0.20)]
        );

        // 3 years of months, so the yearly-totals/year-over-year widgets
        // have real multi-year spread to compare rather than a single year.
        foreach (range(35, 0) as $monthsAgo) {
            $tripsThisMonth = $monthsAgo === 0 ? 2 : fake()->numberBetween(2, 4);

            for ($i = 0; $i < $tripsThisMonth; $i++) {
                $shoppedOn = Carbon::now()->subMonths($monthsAgo)->startOfMonth()->addDays(fake()->numberBetween(0, 27));

                $trip = Trip::create([
                    'shopped_on' => $shoppedOn,
                    'shop_id' => fake()->randomElement($shops)?->id,
                    'discount' => Money::ofMinor(fake()->boolean(25) ? fake()->numberBetween(50, 300) : 0, $currency),
                ]);

                foreach ($products->random(fake()->numberBetween(4, 6)) as $product) {
                    $progressToNow = (35 - $monthsAgo) / 35;
                    $drift = $priceDriftFactors[$product->upc];
                    $trendPrice = $product->price->getMinorAmount()->toInt() * (1 - $drift + ($drift * $progressToNow));
                    $noise = fake()->numberBetween(-3, 3) / 100;
                    $unitPrice = max(1, (int) round($trendPrice * (1 + $noise)));

                    $trip->purchases()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->product_name,
                        'entry_type' => 'scan',
                        'quantity' => fake()->numberBetween(1, 3),
                        'unit_price' => Money::ofMinor($unitPrice, $currency),
                    ]);
                }
            }
        }
    }
}
