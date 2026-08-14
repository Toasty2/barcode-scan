<?php

namespace Tests\Feature;

use App\Models\BudgetChange;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BudgetChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_months_with_budget_is_empty_when_no_budget_has_ever_been_set(): void
    {
        $this->assertCount(0, BudgetChange::monthsWithBudget());
    }

    public function test_months_with_budget_covers_every_month_from_the_earliest_change_to_now(): void
    {
        Carbon::setTestNow('2026-08-15');

        BudgetChange::create(['amount' => Money::ofMinor(20000, 'GBP'), 'effective_from' => '2026-06-01']);

        $months = BudgetChange::monthsWithBudget();

        $this->assertSame(['06/2026', '07/2026', '08/2026'], $months->map(fn ($month) => $month->format('m/Y'))->all());

        Carbon::setTestNow();
    }
}
