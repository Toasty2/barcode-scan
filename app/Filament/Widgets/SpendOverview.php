<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasResponsiveStatsColumns;
use App\Models\BudgetChange;
use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SpendOverview extends StatsOverviewWidget
{
    use HasResponsiveStatsColumns;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $locale = config('money.default_locale');
        $now = Carbon::now();

        $thisMonthSpend = Trip::netSpendForMonth($now);
        $thisMonthBudget = BudgetChange::amountForMonth($now);
        $remaining = $thisMonthBudget?->minus($thisMonthSpend);

        $thisYearSpend = Trip::netSpendForYear($now);

        return [
            Stat::make(__('This month'), $thisMonthSpend->formatToLocale($locale))
                ->description(match (true) {
                    $remaining === null => __('No budget set for this month'),
                    ! $remaining->isNegative() => __(':amount under budget', ['amount' => $remaining->formatToLocale($locale)]),
                    default => __(':amount over budget', ['amount' => $remaining->abs()->formatToLocale($locale)]),
                })
                ->color(match (true) {
                    $remaining === null => 'gray',
                    ! $remaining->isNegative() => 'success',
                    default => 'danger',
                }),

            Stat::make(__('This year'), $thisYearSpend->formatToLocale($locale)),
        ];
    }
}
