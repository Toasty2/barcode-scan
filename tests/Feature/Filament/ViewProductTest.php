<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Widgets\ProductOverview;
use App\Filament\Widgets\ProductPriceHistoryChart;
use App\Models\Product;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class ViewProductTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(): Product
    {
        return Product::create([
            'upc' => '5000112637922',
            'product_name' => 'Coca Cola',
            'price' => 150,
            'last_confirmed' => now(),
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $product = $this->createProduct();

        $this->get("/admin/products/{$product->upc}")->assertRedirect('/admin/login');
    }

    public function test_it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        $product = $this->createProduct();

        Livewire::test(ViewProduct::class, ['record' => $product->getKey()])->assertSuccessful();
    }

    public function test_overview_shows_the_products_fields_as_stats(): void
    {
        $product = $this->createProduct();

        $widget = new ProductOverview();
        $widget->record = $product;

        $stats = (new ReflectionMethod(ProductOverview::class, 'getStats'))->invoke($widget);

        $this->assertSame('Coca Cola', $stats[1]->getValue());
        $this->assertSame('£1.50', $stats[2]->getValue());
    }

    public function test_overview_truncates_a_long_upc_and_keeps_the_full_value_in_the_title_attribute(): void
    {
        $product = Product::create([
            'upc' => '12345678901234567890',
            'product_name' => 'Long UPC Item',
            'price' => 100,
            'last_confirmed' => now(),
        ]);

        $widget = new ProductOverview();
        $widget->record = $product;

        $stats = (new ReflectionMethod(ProductOverview::class, 'getStats'))->invoke($widget);

        $this->assertNotSame($product->upc, $stats[0]->getValue());
        $this->assertStringStartsWith('1234567890123', $stats[0]->getValue());
        $this->assertSame($product->upc, $stats[0]->getExtraAttributes()['title']);
    }

    public function test_chart_has_no_data_points_when_the_product_has_no_purchase_history(): void
    {
        $widget = new ProductPriceHistoryChart();
        $widget->record = $this->createProduct();

        $data = (new ReflectionMethod(ProductPriceHistoryChart::class, 'getData'))->invoke($widget);

        $this->assertSame([], $data['datasets'][0]['data']);
        $this->assertSame([], $data['labels']);
    }

    public function test_chart_shows_price_history_for_the_product(): void
    {
        $product = $this->createProduct();

        $olderTrip = Trip::create(['shopped_on' => '2026-01-01', 'discount' => 0]);
        $olderTrip->purchases()->create([
            'upc' => $product->upc,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => 130,
        ]);

        $newerTrip = Trip::create(['shopped_on' => '2026-02-01', 'discount' => 0]);
        $newerTrip->purchases()->create([
            'upc' => $product->upc,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => 150,
        ]);

        $widget = new ProductPriceHistoryChart();
        $widget->record = $product;

        $data = (new ReflectionMethod(ProductPriceHistoryChart::class, 'getData'))->invoke($widget);

        $this->assertSame([1.3, 1.5], $data['datasets'][0]['data']);
        $this->assertSame(['01/01/2026', '01/02/2026'], $data['labels']);
    }
}
