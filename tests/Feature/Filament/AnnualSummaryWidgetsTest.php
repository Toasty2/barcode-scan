<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\AnnualBudgetAdherence;
use App\Filament\Widgets\AnnualOverview;
use App\Filament\Widgets\AnnualSpendByMonthChart;
use App\Models\BudgetChange;
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
}
