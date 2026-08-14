<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Trip;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function createPurchase(Product $product, string $date, int $unitPrice): void
    {
        $trip = Trip::create(['shopped_on' => $date, 'discount' => 0]);
        $trip->purchases()->create([
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => Money::ofMinor($unitPrice, 'GBP'),
        ]);
    }

    public function test_price_history_for_product_is_ordered_oldest_first(): void
    {
        $product = Product::create(['upc' => '123', 'product_name' => 'Item', 'price' => Money::ofMinor(150, 'GBP'), 'last_confirmed' => now()]);

        $this->createPurchase($product, '2026-03-01', 150);
        $this->createPurchase($product, '2026-01-01', 100);
        $this->createPurchase($product, '2026-02-01', 125);

        $history = Purchase::priceHistoryForProduct($product);

        $this->assertSame([100, 125, 150], $history->pluck('price')->map(fn ($price) => $price->getMinorAmount()->toInt())->all());
        $this->assertTrue($history->first()['date']->isSameDay(Carbon::parse('2026-01-01')));
    }

    public function test_price_history_for_product_only_includes_that_products_purchases(): void
    {
        $product = Product::create(['upc' => '123', 'product_name' => 'Item', 'price' => Money::ofMinor(100, 'GBP'), 'last_confirmed' => now()]);
        $otherProduct = Product::create(['upc' => '456', 'product_name' => 'Other item', 'price' => Money::ofMinor(999, 'GBP'), 'last_confirmed' => now()]);

        $this->createPurchase($product, '2026-01-01', 100);
        $this->createPurchase($otherProduct, '2026-01-02', 999);

        $history = Purchase::priceHistoryForProduct($product);

        $this->assertCount(1, $history);
        $this->assertSame(100, $history->first()['price']->getMinorAmount()->toInt());
    }

    public function test_price_history_for_product_is_empty_when_never_purchased(): void
    {
        $product = Product::create(['upc' => '123', 'product_name' => 'Item', 'price' => Money::ofMinor(100, 'GBP'), 'last_confirmed' => now()]);

        $history = Purchase::priceHistoryForProduct($product);

        $this->assertCount(0, $history);
    }

    public function test_price_history_for_product_spans_the_full_succession_chain(): void
    {
        $original = Product::create(['upc' => '111', 'product_name' => 'Squash 1.75L', 'price' => Money::ofMinor(150, 'GBP'), 'last_confirmed' => now()]);
        $successor = Product::create([
            'upc' => '222',
            'product_name' => 'Squash 1.5L',
            'price' => Money::ofMinor(160, 'GBP'),
            'last_confirmed' => now(),
            'replaces_product_id' => $original->id,
        ]);

        $this->createPurchase($original, '2026-01-01', 150);
        $this->createPurchase($successor, '2026-02-01', 160);

        $history = Purchase::priceHistoryForProduct($successor);

        $this->assertSame([150, 160], $history->pluck('price')->map(fn ($price) => $price->getMinorAmount()->toInt())->all());
        $this->assertTrue($history->first()['date']->isSameDay(Carbon::parse('2026-01-01')));
    }
}
