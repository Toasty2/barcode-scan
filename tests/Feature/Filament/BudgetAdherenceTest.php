<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\BudgetAdherence;
use App\Filament\Widgets\BudgetAdherenceChart;
use App\Models\BudgetChange;
use App\Models\Trip;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class BudgetAdherenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/budget-adherence')->assertRedirect('/admin/login');
    }

    public function test_it_renders_successfully(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(BudgetAdherence::class)->assertSuccessful();
    }

    public function test_chart_is_empty_when_no_budget_has_ever_been_set(): void
    {
        $widget = new BudgetAdherenceChart();

        $data = (new ReflectionMethod(BudgetAdherenceChart::class, 'getData'))->invoke($widget);

        $this->assertSame([], $data['labels']);
        $this->assertSame([], $data['datasets'][0]['data']);
        $this->assertSame([], $data['datasets'][1]['data']);
    }

    public function test_chart_shows_budget_and_spend_for_every_month_with_a_budget(): void
    {
        Carbon::setTestNow('2026-08-15');

        BudgetChange::create(['amount' => Money::ofMinor(20000, 'GBP'), 'effective_from' => '2026-07-01']);

        $trip = Trip::create(['shopped_on' => '2026-07-10', 'discount' => 0]);
        $trip->purchases()->create([
            'upc' => null,
            'product_name' => 'Item',
            'entry_type' => 'lump_sum',
            'quantity' => 1,
            'unit_price' => Money::ofMinor(5000, 'GBP'),
        ]);

        $widget = new BudgetAdherenceChart();

        $data = (new ReflectionMethod(BudgetAdherenceChart::class, 'getData'))->invoke($widget);

        $this->assertSame(['Jul 2026', 'Aug 2026'], $data['labels']);
        $this->assertSame([200.0, 200.0], $data['datasets'][0]['data']);
        $this->assertSame([50.0, 0.0], $data['datasets'][1]['data']);

        Carbon::setTestNow();
    }
}
