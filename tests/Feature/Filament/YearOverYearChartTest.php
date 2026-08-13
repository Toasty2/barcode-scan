<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\YearOverYearChart;
use App\Models\Product;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class YearOverYearChartTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createTripWithSpend(string $date, int $minorUnits): void
    {
        $product = Product::factory()->create(['price' => $minorUnits]);
        $trip = Trip::create(['shopped_on' => $date, 'discount' => 0]);
        $trip->purchases()->create([
            'upc' => $product->upc,
            'product_name' => $product->product_name,
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => $minorUnits,
        ]);
    }

    public function test_it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(YearOverYearChart::class)->assertSuccessful();
    }

    public function test_months_after_the_current_month_are_null_not_zero(): void
    {
        Carbon::setTestNow('2026-03-15');

        $this->createTripWithSpend('2026-01-10', 1000);

        $getData = new ReflectionMethod(YearOverYearChart::class, 'getData');
        $data = $getData->invoke(new YearOverYearChart());

        $dataset = $data['datasets'][0]['data'];

        // Jan (index 0) has spend, Feb (index 1) has none but already
        // happened, both should be real numbers — only months from April
        // (index 3) onwards, which haven't happened yet, should be null.
        $this->assertSame(10.0, $dataset[0]);
        $this->assertSame(0.0, $dataset[1]);
        $this->assertSame(0.0, $dataset[2]);
        $this->assertNull($dataset[3]);
        $this->assertNull($dataset[11]);
    }
}
