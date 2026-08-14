<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\Trip;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TripTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_years_with_trips_covers_every_year_from_the_earliest_trip_to_now(): void
    {
        Carbon::setTestNow('2026-08-13');

        Trip::create(['shopped_on' => '2024-03-01', 'discount' => 0]);
        Trip::create(['shopped_on' => '2025-11-20', 'discount' => 0]);

        $this->assertSame([2024, 2025, 2026], Trip::yearsWithTrips()->all());
    }

    public function test_years_with_trips_returns_the_current_year_when_there_are_no_trips(): void
    {
        Carbon::setTestNow('2026-08-13');

        $this->assertSame([2026], Trip::yearsWithTrips()->all());
    }

    public function test_count_for_year_only_counts_trips_in_that_calendar_year(): void
    {
        Trip::create(['shopped_on' => '2025-01-01', 'discount' => 0]);
        Trip::create(['shopped_on' => '2025-12-31', 'discount' => 0]);
        Trip::create(['shopped_on' => '2026-01-01', 'discount' => 0]);

        $this->assertSame(2, Trip::countForYear(2025));
        $this->assertSame(1, Trip::countForYear(2026));
        $this->assertSame(0, Trip::countForYear(2024));
    }

    public function test_item_count_for_year_sums_purchase_quantities_in_that_year(): void
    {
        $trip = Trip::create(['shopped_on' => '2025-06-01', 'discount' => 0]);
        $trip->purchases()->create([
            'upc' => null,
            'product_name' => 'A',
            'entry_type' => 'scan',
            'quantity' => 3,
            'unit_price' => Money::ofMinor(100, 'GBP'),
        ]);
        $trip->purchases()->create([
            'upc' => null,
            'product_name' => 'B',
            'entry_type' => 'scan',
            'quantity' => 2,
            'unit_price' => Money::ofMinor(200, 'GBP'),
        ]);

        $otherYearTrip = Trip::create(['shopped_on' => '2026-06-01', 'discount' => 0]);
        $otherYearTrip->purchases()->create([
            'upc' => null,
            'product_name' => 'C',
            'entry_type' => 'scan',
            'quantity' => 10,
            'unit_price' => Money::ofMinor(100, 'GBP'),
        ]);

        $this->assertSame(5, Trip::itemCountForYear(2025));
        $this->assertSame(10, Trip::itemCountForYear(2026));
    }

    public function test_net_spend_by_shop_for_year_groups_by_shop_including_unassigned(): void
    {
        $tesco = Shop::create(['name' => 'Tesco', 'is_default' => false]);
        $waitrose = Shop::create(['name' => 'Waitrose', 'is_default' => false]);

        $tescoTrip = Trip::create(['shopped_on' => '2025-01-01', 'shop_id' => $tesco->id, 'discount' => Money::ofMinor(100, 'GBP')]);
        $tescoTrip->purchases()->create([
            'upc' => null,
            'product_name' => 'A',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => Money::ofMinor(1000, 'GBP'),
        ]);

        $waitroseTrip = Trip::create(['shopped_on' => '2025-02-01', 'shop_id' => $waitrose->id, 'discount' => 0]);
        $waitroseTrip->purchases()->create([
            'upc' => null,
            'product_name' => 'B',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => Money::ofMinor(500, 'GBP'),
        ]);

        $noShopTrip = Trip::create(['shopped_on' => '2025-03-01', 'discount' => 0]);
        $noShopTrip->purchases()->create([
            'upc' => null,
            'product_name' => 'C',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => Money::ofMinor(300, 'GBP'),
        ]);

        $breakdown = Trip::netSpendByShopForYear(2025);

        $this->assertCount(3, $breakdown);

        $tescoRow = $breakdown->first(fn (array $row) => $row['shop']?->is($tesco));
        $this->assertSame(900, $tescoRow['spend']->getMinorAmount()->toInt());

        $waitroseRow = $breakdown->first(fn (array $row) => $row['shop']?->is($waitrose));
        $this->assertSame(500, $waitroseRow['spend']->getMinorAmount()->toInt());

        $noShopRow = $breakdown->first(fn (array $row) => $row['shop'] === null);
        $this->assertSame(300, $noShopRow['spend']->getMinorAmount()->toInt());
    }
}
