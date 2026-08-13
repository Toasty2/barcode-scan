<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Shops\Pages\CreateShop;
use App\Filament\Resources\Shops\Pages\ListShops;
use App\Filament\Resources\Trips\Pages\EditTrip;
use App\Filament\Resources\Trips\Pages\ListTrips;
use App\Filament\Resources\Trips\RelationManagers\PurchasesRelationManager;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/products')->assertRedirect('/admin/login');
    }

    public function test_an_authenticated_user_can_access_the_admin_panel(): void
    {
        // Deliberately a real HTTP request, not Livewire::test() — this is
        // the only test in the suite that exercises the actual middleware
        // stack (Filament\Http\Middleware\Authenticate), which is where
        // User::canAccessPanel() is enforced. Livewire::test() instantiates
        // components directly and never runs route middleware, so it can't
        // catch a regression here.
        $this->actingAs($this->user)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_products_list_shows_records(): void
    {
        $product = Product::create([
            'upc' => '5010102115521',
            'product_name' => 'Robinsons Dbl Con Sum Fruits 1.75ltr',
            'price' => 300,
            'last_confirmed' => now(),
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListProducts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$product]);
    }

    public function test_products_list_is_sorted_by_most_recently_confirmed_first(): void
    {
        $older = Product::create([
            'upc' => '1111111111111',
            'product_name' => 'Older',
            'price' => 100,
            'last_confirmed' => now()->subDays(10),
        ]);
        $newer = Product::create([
            'upc' => '2222222222222',
            'product_name' => 'Newer',
            'price' => 100,
            'last_confirmed' => now(),
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
    }

    public function test_a_product_can_be_created(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'upc' => '5000112637922',
                'product_name' => 'Coca Cola',
                'price' => 1.50,
                'last_confirmed' => now(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'upc' => '5000112637922',
            'product_name' => 'Coca Cola',
        ]);
    }

    public function test_trips_list_shows_records(): void
    {
        $trip = Trip::create(['shopped_on' => today(), 'discount' => 250]);

        $this->actingAs($this->user);

        Livewire::test(ListTrips::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$trip]);
    }

    public function test_trips_list_is_sorted_most_recent_first(): void
    {
        $older = Trip::create(['shopped_on' => today()->subDays(10), 'discount' => 0]);
        $newer = Trip::create(['shopped_on' => today(), 'discount' => 0]);

        $this->actingAs($this->user);

        Livewire::test(ListTrips::class)
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
    }

    public function test_trip_edit_page_shows_its_purchases_via_relation_manager(): void
    {
        Product::create([
            'upc' => '5010102115521',
            'product_name' => 'Robinsons',
            'price' => 300,
            'last_confirmed' => now(),
        ]);

        $trip = Trip::create(['shopped_on' => today(), 'discount' => 0]);
        $purchase = $trip->purchases()->create([
            'upc' => '5010102115521',
            'product_name' => 'Robinsons',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => 300,
        ]);

        $this->actingAs($this->user);

        Livewire::test(EditTrip::class, ['record' => $trip->getRouteKey()])
            ->assertSuccessful();

        Livewire::test(PurchasesRelationManager::class, [
            'ownerRecord' => $trip,
            'pageClass' => EditTrip::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$purchase]);
    }

    public function test_shops_list_shows_records(): void
    {
        $shop = Shop::create(['name' => 'Tesco', 'is_default' => true]);

        $this->actingAs($this->user);

        Livewire::test(ListShops::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$shop]);
    }

    public function test_a_shop_can_be_created(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateShop::class)
            ->fillForm([
                'name' => 'Waitrose',
                'is_default' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('shops', [
            'name' => 'Waitrose',
            'is_default' => false,
        ]);
    }

    public function test_purchases_list_shows_records(): void
    {
        $trip = Trip::create(['shopped_on' => today(), 'discount' => 0]);
        $purchase = $trip->purchases()->create([
            'upc' => null,
            'product_name' => 'Test Item',
            'entry_type' => 'lump_sum',
            'quantity' => 1,
            'unit_price' => 1000,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListPurchases::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$purchase]);
    }

    public function test_purchases_list_is_sorted_by_most_recent_trip_first(): void
    {
        $olderTrip = Trip::create(['shopped_on' => today()->subDays(10), 'discount' => 0]);
        $olderPurchase = $olderTrip->purchases()->create([
            'upc' => null,
            'product_name' => 'Older item',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $newerTrip = Trip::create(['shopped_on' => today(), 'discount' => 0]);
        $newerPurchase = $newerTrip->purchases()->create([
            'upc' => null,
            'product_name' => 'Newer item',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListPurchases::class)
            ->assertCanSeeTableRecords([$newerPurchase, $olderPurchase], inOrder: true);
    }
}
