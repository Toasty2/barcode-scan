<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function createPurchase(string $upc, string $date, int $unitPrice): void
    {
        Product::firstOrCreate(
            ['upc' => $upc],
            ['product_name' => 'Item', 'price' => $unitPrice, 'last_confirmed' => now()],
        );

        $trip = Trip::create(['shopped_on' => $date, 'discount' => 0]);
        $trip->purchases()->create([
            'upc' => $upc,
            'product_name' => 'Item',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => $unitPrice,
        ]);
    }

    public function test_price_history_for_upc_is_ordered_oldest_first(): void
    {
        $this->createPurchase('123', '2026-03-01', 150);
        $this->createPurchase('123', '2026-01-01', 100);
        $this->createPurchase('123', '2026-02-01', 125);

        $history = Purchase::priceHistoryForUpc('123');

        $this->assertSame([100, 125, 150], $history->pluck('price')->map(fn ($price) => $price->minorUnits)->all());
        $this->assertTrue($history->first()['date']->isSameDay(Carbon::parse('2026-01-01')));
    }

    public function test_price_history_for_upc_only_includes_that_products_purchases(): void
    {
        $this->createPurchase('123', '2026-01-01', 100);
        $this->createPurchase('456', '2026-01-02', 999);

        $history = Purchase::priceHistoryForUpc('123');

        $this->assertCount(1, $history);
        $this->assertSame(100, $history->first()['price']->minorUnits);
    }

    public function test_price_history_for_upc_is_empty_when_never_purchased(): void
    {
        $history = Purchase::priceHistoryForUpc('does-not-exist');

        $this->assertCount(0, $history);
    }
}
