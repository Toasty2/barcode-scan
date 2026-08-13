<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasResponsiveStatsColumns;
use App\Models\BudgetChange;
use App\Models\Trip;
use App\Support\Money\Price;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AnnualBudgetAdherence extends StatsOverviewWidget
{
    use HasResponsiveStatsColumns;
    use InteractsWithPageFilters;

    // Shown only on the Annual Summary page, not auto-attached to the
    // Dashboard.
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $year = (int) ($this->pageFilters['year'] ?? Carbon::now()->year);

        [$totalBudget, $totalSpend, $monthsWithBudget] = $this->totalsForBudgetedMonths($year);

        if ($totalBudget === null) {
            return [
                Stat::make(__('Budget adherence'), __('No budget set'))
                    ->description(__('No budget was in effect during :year', ['year' => $year]))
                    ->color('gray'),
            ];
        }

        $remaining = $totalBudget->subtract($totalSpend);

        return [
            Stat::make(__('Budget'), $totalBudget->format())
                ->description(trans_choice(
                    'Across :count month with a budget set|Across :count months with a budget set',
                    $monthsWithBudget,
                    ['count' => $monthsWithBudget],
                )),
            Stat::make(__('Spend'), $totalSpend->format())
                ->description(match (true) {
                    $remaining->minorUnits === 0 => __('Exactly on budget'),
                    ! $remaining->isNegative() => __(':amount under budget', ['amount' => $remaining->format()]),
                    default => __(':amount over budget', ['amount' => $remaining->abs()->format()]),
                })
                ->color(match (true) {
                    $remaining->minorUnits === 0 => 'gray',
                    ! $remaining->isNegative() => 'success',
                    default => 'danger',
                }),
        ];
    }

    /**
     * Sums budget and spend only across the selected year's months that
     * actually had a budget in effect, so the comparison never pits a
     * partial-year budget against a full-year spend figure.
     *
     * @return array{0: ?Price, 1: Price, 2: int}
     */
    private function totalsForBudgetedMonths(int $year): array
    {
        $currencyClass = config('money.default_currency');

        $totalBudget = null;
        $totalSpend = new Price(0, new $currencyClass());
        $monthsWithBudget = 0;

        foreach (range(1, 12) as $month) {
            $monthStart = Carbon::create($year, $month, 1);
            $budget = BudgetChange::amountForMonth($monthStart);

            if ($budget === null) {
                continue;
            }

            $monthsWithBudget++;
            $totalBudget = $totalBudget ? $totalBudget->add($budget) : $budget;
            $totalSpend = $totalSpend->add(Trip::netSpendForMonth($monthStart));
        }

        return [$totalBudget, $totalSpend, $monthsWithBudget];
    }
}
