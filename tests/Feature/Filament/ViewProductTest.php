<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Widgets\ProductOverview;
use App\Filament\Widgets\ProductPriceHistoryChart;
use App\Models\Product;
use App\Models\Trip;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            'price' => Money::ofMinor(150, 'GBP'),
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

        Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])->assertSuccessful();
    }

    public function test_photo_is_hidden_when_the_product_has_no_image(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->createProduct();

        Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])
            ->assertDontSee('/storage/products');
    }

    public function test_photo_is_shown_when_the_product_has_an_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/test.png', 'fake-image-content');

        $this->actingAs(User::factory()->create());
        $product = $this->createProduct();
        $product->update(['image_path' => 'products/test.png']);

        Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])
            ->assertSee('/storage/products/test.png')
            ->assertSee('alt="Coca Cola"', escape: false);
    }

    public function test_overview_shows_the_products_price_and_last_confirmed_date_as_stats(): void
    {
        $product = $this->createProduct();

        $widget = new ProductOverview();
        $widget->record = $product;

        $stats = (new ReflectionMethod(ProductOverview::class, 'getStats'))->invoke($widget);

        $this->assertCount(4, $stats);
        $this->assertSame('£1.50', $stats[0]->getValue());
        $this->assertSame($product->last_confirmed->format('d/m/Y'), $stats[1]->getValue());
    }

    public function test_price_delta_shows_a_placeholder_when_never_purchased(): void
    {
        $widget = new ProductOverview();
        $widget->record = $this->createProduct();

        $stat = (new ReflectionMethod(ProductOverview::class, 'priceDeltaStat'))->invoke($widget);

        $this->assertSame('No purchase history yet', $stat->getValue());
    }

    public function test_price_delta_compares_the_first_and_most_recent_price(): void
    {
        $product = $this->createProduct();

        $olderTrip = Trip::create(['shopped_on' => '2026-01-01', 'discount' => 0]);
        $olderTrip->purchases()->create([
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => Money::ofMinor(130, 'GBP'),
        ]);

        $newerTrip = Trip::create(['shopped_on' => '2026-02-01', 'discount' => 0]);
        $newerTrip->purchases()->create([
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => Money::ofMinor(150, 'GBP'),
        ]);

        $widget = new ProductOverview();
        $widget->record = $product;

        $stat = (new ReflectionMethod(ProductOverview::class, 'priceDeltaStat'))->invoke($widget);

        $this->assertSame('£0.20', $stat->getValue());
        $this->assertSame('More expensive than first purchase on 01/01/2026', $stat->getDescription());
        $this->assertSame('danger', $stat->getColor());
    }

    public function test_total_purchases_sums_quantity_across_all_trips(): void
    {
        $product = $this->createProduct();

        $firstTrip = Trip::create(['shopped_on' => '2026-01-01', 'discount' => 0]);
        $firstTrip->purchases()->create([
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => 2,
            'unit_price' => Money::ofMinor(150, 'GBP'),
        ]);

        $secondTrip = Trip::create(['shopped_on' => '2026-02-01', 'discount' => 0]);
        $secondTrip->purchases()->create([
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => 3,
            'unit_price' => Money::ofMinor(150, 'GBP'),
        ]);

        $widget = new ProductOverview();
        $widget->record = $product;

        $stat = (new ReflectionMethod(ProductOverview::class, 'totalPurchasesStat'))->invoke($widget);

        $this->assertSame('5', $stat->getValue());
    }

    public function test_page_title_is_the_products_name_with_the_upc_as_a_subheading(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->createProduct();

        $component = Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()]);

        $this->assertSame('Coca Cola', $component->instance()->getTitle());
        $this->assertSame('5000112637922', $component->instance()->getSubheading());
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
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => Money::ofMinor(130, 'GBP'),
        ]);

        $newerTrip = Trip::create(['shopped_on' => '2026-02-01', 'discount' => 0]);
        $newerTrip->purchases()->create([
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => Money::ofMinor(150, 'GBP'),
        ]);

        $widget = new ProductPriceHistoryChart();
        $widget->record = $product;

        $data = (new ReflectionMethod(ProductPriceHistoryChart::class, 'getData'))->invoke($widget);

        $this->assertSame([1.3, 1.5], $data['datasets'][0]['data']);
        $this->assertSame(['01/01/2026', '01/02/2026'], $data['labels']);
    }
}
