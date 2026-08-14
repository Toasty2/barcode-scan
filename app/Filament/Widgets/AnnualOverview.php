<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasResponsiveStatsColumns;
use App\Models\Trip;
use Brick\Math\RoundingMode;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AnnualOverview extends StatsOverviewWidget
{
    use HasResponsiveStatsColumns;
    use InteractsWithPageFilters;

    // Shown only on the Annual Summary page, not auto-attached to the
    // Dashboard.
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $locale = config('money.default_locale');
        $year = (int) ($this->pageFilters['year'] ?? Carbon::now()->year);

        $spend = Trip::netSpendForYear(Carbon::create($year));
        $tripCount = Trip::countForYear($year);
        $itemCount = Trip::itemCountForYear($year);

        return [
            Stat::make(__('Total spend'), $spend->formatToLocale($locale)),
            Stat::make(__('Trips'), (string) $tripCount),
            Stat::make(__('Items bought'), (string) $itemCount),
            Stat::make(__('Average per trip'), $tripCount > 0 ? $spend->dividedBy($tripCount, RoundingMode::HalfUp)->formatToLocale($locale) : '—'),
        ];
    }
}
