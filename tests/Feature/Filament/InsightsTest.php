<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Insights;
use App\Filament\Widgets\InsightsOverview;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InsightsTest extends TestCase
{
    use RefreshDatabase;

    private function createTrip(string $date, ?int $shopId, string $upc, int $quantity, int $unitPrice, int $discount = 0): Trip
    {
        $product = Product::firstOrCreate(
            ['upc' => $upc],
            ['product_name' => 'Item '.$upc, 'price' => $unitPrice, 'last_confirmed' => now()],
        );

        $trip = Trip::create(['shopped_on' => $date, 'shop_id' => $shopId, 'discount' => $discount]);
        $trip->purchases()->create([
            'product_id' => $product->id,
            'product_name' => 'Item '.$upc,
            'entry_type' => 'scan',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ]);

        return $trip;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/insights')->assertRedirect('/admin/login');
    }

    public function test_it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Insights::class)->assertSuccessful();
    }

    public function test_all_stats_show_a_placeholder_when_there_is_no_data(): void
    {
        Livewire::test(InsightsOverview::class)->assertSeeText('Not enough data yet');
    }

    public function test_favourite_shop_is_the_one_with_the_most_trips(): void
    {
        $tesco = Shop::create(['name' => 'Tesco', 'is_default' => true]);
        $waitrose = Shop::create(['name' => 'Waitrose', 'is_default' => false]);

        $this->createTrip('2026-01-01', $tesco->id, '111', 1, 100);
        $this->createTrip('2026-01-08', $tesco->id, '111', 1, 100);
        $this->createTrip('2026-01-15', $waitrose->id, '111', 1, 100);

        Livewire::test(InsightsOverview::class)
            ->assertSeeText('Tesco')
            ->assertSeeText('2 trips so far');
    }

    public function test_most_bought_product_is_ranked_by_total_quantity(): void
    {
        Product::create(['upc' => '111', 'product_name' => 'Beans', 'price' => 100, 'last_confirmed' => now()]);
        Product::create(['upc' => '222', 'product_name' => 'Bread', 'price' => 150, 'last_confirmed' => now()]);

        $this->createTrip('2026-01-01', null, '111', 5, 100);
        $this->createTrip('2026-01-08', null, '222', 1, 150);

        Livewire::test(InsightsOverview::class)
            ->assertSeeText('Beans')
            ->assertSeeText('Bought 5 times');
    }

    public function test_biggest_trip_is_net_of_its_own_discount(): void
    {
        $this->createTrip('2026-01-01', null, '111', 1, 300);
        $this->createTrip('2026-01-08', null, '222', 1, 2000, discount: 500);

        // £20.00 gross minus a £5.00 discount = £15.00 net — bigger than the
        // other trip's £3.00, and not the same as its own £20.00 gross.
        Livewire::test(InsightsOverview::class)->assertSeeText('£15.00');
    }
}
