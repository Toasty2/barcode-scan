<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\AnnualBudgetAdherence;
use App\Filament\Widgets\AnnualOverview;
use App\Filament\Widgets\AnnualSpendByMonthChart;
use App\Filament\Widgets\AnnualSpendByShopChart;
use App\Models\BudgetChange;
use App\Models\Shop;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class AnnualSummaryWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createTripWithSpend(string $date, int $minorUnits): void
    {
        $trip = Trip::create(['shopped_on' => $date, 'discount' => 0]);
        $trip->purchases()->create([
            'upc' => null,
            'product_name' => 'Item',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => $minorUnits,
        ]);
    }

    public function test_annual_overview_reflects_the_selected_year(): void
    {
        $this->createTripWithSpend('2025-01-01', 1000);
        $this->createTripWithSpend('2026-01-01', 5000);

        $component = Livewire::test(AnnualOverview::class, ['pageFilters' => ['year' => 2025]]);
        $component->assertSeeText('£10.00');
        $component->assertDontSeeText('£50.00');
    }

    public function test_annual_overview_shows_a_placeholder_average_when_there_are_no_trips(): void
    {
        Carbon::setTestNow('2026-08-13');
        $this->createTripWithSpend('2025-01-01', 1000);

        $component = Livewire::test(AnnualOverview::class, ['pageFilters' => ['year' => 2026]]);

        $component->assertSeeText('—');
    }

    public function test_budget_adherence_reports_no_budget_when_none_was_in_effect(): void
    {
        $this->createTripWithSpend('2025-01-01', 1000);

        Livewire::test(AnnualBudgetAdherence::class, ['pageFilters' => ['year' => 2025]])
            ->assertSeeText('No budget set');
    }

    public function test_budget_adherence_compares_spend_to_budget_for_budgeted_months(): void
    {
        BudgetChange::create(['amount' => 10000, 'effective_from' => '2025-01-01']);
        $this->createTripWithSpend('2025-01-15', 4000);

        Livewire::test(AnnualBudgetAdherence::class, ['pageFilters' => ['year' => 2025]])
            ->assertSeeText('£1,160.00 under budget');
    }

    public function test_spend_by_month_chart_nulls_months_after_the_current_month_for_the_current_year(): void
    {
        Carbon::setTestNow('2026-03-15');
        $this->createTripWithSpend('2026-01-10', 1000);

        $widget = new AnnualSpendByMonthChart();
        $widget->pageFilters = ['year' => 2026];

        $data = (new ReflectionMethod(AnnualSpendByMonthChart::class, 'getData'))->invoke($widget)['datasets'][0]['data'];

        $this->assertSame(10.0, $data[0]);
        $this->assertSame(0.0, $data[1]);
        $this->assertNull($data[3]);
    }

    public function test_spend_by_month_chart_does_not_null_a_fully_past_year(): void
    {
        Carbon::setTestNow('2026-08-13');
        $this->createTripWithSpend('2025-01-10', 1000);

        $widget = new AnnualSpendByMonthChart();
        $widget->pageFilters = ['year' => 2025];

        $data = (new ReflectionMethod(AnnualSpendByMonthChart::class, 'getData'))->invoke($widget)['datasets'][0]['data'];

        $this->assertNotNull($data[11]);
    }

    public function test_spend_by_shop_chart_includes_the_current_partial_years_data(): void
    {
        Carbon::setTestNow('2026-08-13');
        $shop = Shop::create(['name' => 'Tesco', 'is_default' => true]);
        $trip = Trip::create(['shopped_on' => '2026-03-01', 'shop_id' => $shop->id, 'discount' => 0]);
        $trip->purchases()->create([
            'upc' => null,
            'product_name' => 'Item',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => 1000,
        ]);

        $widget = new AnnualSpendByShopChart();
        $widget->pageFilters = ['year' => 2026];

        $data = (new ReflectionMethod(AnnualSpendByShopChart::class, 'getData'))->invoke($widget);

        $this->assertSame([10.0], $data['datasets'][0]['data']);
        $this->assertSame(['Tesco'], $data['labels']);
    }

    public function test_spend_by_shop_chart_data_is_sequentially_keyed_with_multiple_shops(): void
    {
        // A JSON-encoded PHP array with non-sequential keys (e.g. left
        // behind by sortByDesc() without a following values()) serializes
        // as a JS object instead of an array, which silently breaks
        // Chart.js — so this asserts real sequential array shape, not just
        // the right values in any order.
        $tesco = Shop::create(['name' => 'Tesco', 'is_default' => true]);
        $waitrose = Shop::create(['name' => 'Waitrose', 'is_default' => false]);

        $smallerTrip = Trip::create(['shopped_on' => '2026-01-01', 'shop_id' => $tesco->id, 'discount' => 0]);
        $smallerTrip->purchases()->create([
            'upc' => null,
            'product_name' => 'Item',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => 500,
        ]);

        $biggerTrip = Trip::create(['shopped_on' => '2026-02-01', 'shop_id' => $waitrose->id, 'discount' => 0]);
        $biggerTrip->purchases()->create([
            'upc' => null,
            'product_name' => 'Item',
            'entry_type' => 'scan',
            'quantity' => 1,
            'unit_price' => 1500,
        ]);

        $widget = new AnnualSpendByShopChart();
        $widget->pageFilters = ['year' => 2026];

        $data = (new ReflectionMethod(AnnualSpendByShopChart::class, 'getData'))->invoke($widget);

        $this->assertSame([15.0, 5.0], $data['datasets'][0]['data']);
        $this->assertSame(['Waitrose', 'Tesco'], $data['labels']);
        $this->assertSame([0, 1], array_keys($data['datasets'][0]['data']));
        $this->assertSame([0, 1], array_keys($data['labels']));
    }
}
