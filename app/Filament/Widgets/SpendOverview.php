<?php

namespace App\Filament\Widgets;

use App\Models\BudgetChange;
use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SpendOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();

        $thisMonthSpend = Trip::netSpendForMonth($now);
        $thisMonthBudget = BudgetChange::amountForMonth($now);
        $remaining = $thisMonthBudget !== null ? $thisMonthBudget - $thisMonthSpend : null;

        $thisYearSpend = Trip::netSpendForYear($now);

        return [
            Stat::make('This month', '£'.number_format($thisMonthSpend, 2))
                ->description(match (true) {
                    $remaining === null => 'No budget set for this month',
                    $remaining >= 0 => '£'.number_format($remaining, 2).' under budget',
                    default => '£'.number_format(abs($remaining), 2).' over budget',
                })
                ->color(match (true) {
                    $remaining === null => 'gray',
                    $remaining >= 0 => 'success',
                    default => 'danger',
                }),

            Stat::make('This year', '£'.number_format($thisYearSpend, 2)),
        ];
    }
}
