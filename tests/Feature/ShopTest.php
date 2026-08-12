<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_shop_can_be_default_at_a_time(): void
    {
        $tesco = Shop::create(['name' => 'Tesco', 'is_default' => true]);
        $waitrose = Shop::create(['name' => 'Waitrose', 'is_default' => true]);

        $this->assertFalse($tesco->fresh()->is_default);
        $this->assertTrue($waitrose->fresh()->is_default);
    }

    public function test_making_an_existing_shop_default_unsets_the_previous_default(): void
    {
        $tesco = Shop::create(['name' => 'Tesco', 'is_default' => true]);
        $waitrose = Shop::create(['name' => 'Waitrose', 'is_default' => false]);

        $waitrose->update(['is_default' => true]);

        $this->assertFalse($tesco->fresh()->is_default);
        $this->assertTrue($waitrose->fresh()->is_default);
    }

    public function test_default_returns_the_default_shop(): void
    {
        Shop::create(['name' => 'Tesco', 'is_default' => false]);
        $waitrose = Shop::create(['name' => 'Waitrose', 'is_default' => true]);

        $this->assertTrue(Shop::default()->is($waitrose));
    }

    public function test_default_returns_null_when_no_shop_is_default(): void
    {
        Shop::create(['name' => 'Tesco', 'is_default' => false]);

        $this->assertNull(Shop::default());
    }

    public function test_a_trip_can_have_no_shop(): void
    {
        $trip = Trip::create(['shopped_on' => today(), 'discount' => 0]);

        $this->assertNull($trip->shop_id);
        $this->assertNull($trip->shop);
    }

    public function test_a_trip_can_belong_to_a_shop(): void
    {
        $tesco = Shop::create(['name' => 'Tesco', 'is_default' => true]);
        $trip = Trip::create(['shopped_on' => today(), 'shop_id' => $tesco->id, 'discount' => 0]);

        $this->assertTrue($trip->shop->is($tesco));
    }

    public function test_badge_color_derives_a_shade_palette_from_the_stored_colour(): void
    {
        $shop = Shop::create(['name' => 'Tesco', 'colour' => '#00539F', 'is_default' => false]);

        $this->assertIsArray($shop->badgeColor());
    }

    public function test_badge_color_is_null_when_no_colour_is_set(): void
    {
        $shop = Shop::create(['name' => 'Tesco', 'is_default' => false]);

        $this->assertNull($shop->badgeColor());
    }
}
