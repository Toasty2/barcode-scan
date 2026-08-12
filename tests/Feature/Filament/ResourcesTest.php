<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Trips\Pages\EditTrip;
use App\Filament\Resources\Trips\Pages\ListTrips;
use App\Filament\Resources\Trips\RelationManagers\PurchasesRelationManager;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Models\Product;
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

    public function test_products_list_shows_records(): void
    {
        $product = Product::create([
            'upc' => '5010102115521',
            'product_name' => 'Robinsons Dbl Con Sum Fruits 1.75ltr',
            'price' => 3.00,
            'last_confirmed' => now(),
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListProducts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$product]);
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
        $trip = Trip::create(['shopped_on' => today(), 'discount' => 2.50]);

        $this->actingAs($this->user);

        Livewire::test(ListTrips::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$trip]);
    }

    public function test_trip_edit_page_shows_its_purchases_via_relation_manager(): void
    {
        Product::create([
            'upc' => '5010102115521',
            'product_name' => 'Robinsons',
            'price' => 3.00,
            'last_confirmed' => now(),
        ]);

        $trip = Trip::create(['shopped_on' => today(), 'discount' => 0]);
        $purchase = $trip->purchases()->create([
            'upc' => '5010102115521',
            'product_name' => 'Robinsons',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => 3.00,
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

    public function test_purchases_list_shows_records(): void
    {
        $trip = Trip::create(['shopped_on' => today(), 'discount' => 0]);
        $purchase = $trip->purchases()->create([
            'upc' => null,
            'product_name' => 'Test Item',
            'entry_type' => 'lump_sum',
            'quantity' => 1,
            'unit_price' => 10.00,
        ]);

        $this->actingAs($this->user);

        Livewire::test(ListPurchases::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$purchase]);
    }
}
