<?php

namespace Tests\Feature;

use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'items' => [
                [
                    'upc' => null,
                    'product_name' => 'Test Item',
                    'price' => 199,
                    'quantity' => 1,
                    'entry_type' => 'scan',
                ],
            ],
        ], $overrides);
    }

    public function test_a_trip_can_be_submitted_with_an_existing_shop(): void
    {
        $shop = Shop::create(['name' => 'Tesco', 'is_default' => false]);

        $response = $this->postJson('/trips', $this->validPayload(['shop_id' => $shop->id]));

        $response->assertOk()->assertJson(['success' => true, 'shop' => null]);
        $this->assertDatabaseHas('trips', ['shop_id' => $shop->id]);
    }

    public function test_a_trip_can_be_submitted_without_a_shop(): void
    {
        $response = $this->postJson('/trips', $this->validPayload());

        $response->assertOk();
        $this->assertDatabaseHas('trips', ['shop_id' => null]);
    }

    public function test_a_new_shop_is_created_and_assigned_to_the_trip(): void
    {
        $response = $this->postJson('/trips', $this->validPayload([
            'new_shop' => ['name' => 'Waitrose', 'colour' => '#00603A'],
        ]));

        $response->assertOk();

        $shop = Shop::where('name', 'Waitrose')->first();
        $this->assertNotNull($shop);
        $this->assertSame('#00603A', $shop->colour);
        $this->assertFalse($shop->is_default);

        $this->assertDatabaseHas('trips', ['shop_id' => $shop->id]);
        $response->assertJson(['shop' => ['id' => $shop->id, 'name' => 'Waitrose']]);
    }

    public function test_a_new_shop_without_a_colour_is_allowed(): void
    {
        $response = $this->postJson('/trips', $this->validPayload([
            'new_shop' => ['name' => 'Aldi'],
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('shops', ['name' => 'Aldi', 'colour' => null]);
    }

    public function test_a_new_shop_requires_a_name(): void
    {
        $response = $this->postJson('/trips', $this->validPayload([
            'new_shop' => ['name' => '', 'colour' => '#ffffff'],
        ]));

        $response->assertStatus(422);
        $this->assertDatabaseCount('shops', 0);
    }

    public function test_existing_shop_id_takes_precedence_over_new_shop(): void
    {
        $shop = Shop::create(['name' => 'Tesco', 'is_default' => false]);

        $response = $this->postJson('/trips', $this->validPayload([
            'shop_id' => $shop->id,
            'new_shop' => ['name' => 'Should not be created'],
        ]));

        $response->assertOk()->assertJson(['shop' => null]);
        $this->assertDatabaseCount('shops', 1);
        $this->assertDatabaseHas('trips', ['shop_id' => $shop->id]);
    }
}
