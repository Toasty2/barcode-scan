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
        $remaining = $thisMonthBudget?->subtract($thisMonthSpend);

        $thisYearSpend = Trip::netSpendForYear($now);

        return [
            Stat::make('This month', $thisMonthSpend->format())
                ->description(match (true) {
                    $remaining === null => 'No budget set for this month',
                    ! $remaining->isNegative() => $remaining->format().' under budget',
                    default => $remaining->abs()->format().' over budget',
                })
                ->color(match (true) {
                    $remaining === null => 'gray',
                    ! $remaining->isNegative() => 'success',
                    default => 'danger',
                }),

            Stat::make('This year', $thisYearSpend->format()),
        ];
    }
}
