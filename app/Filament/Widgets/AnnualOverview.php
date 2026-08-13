<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AnnualOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    // Shown only on the Annual Summary page, not auto-attached to the
    // Dashboard.
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $year = (int) ($this->pageFilters['year'] ?? Carbon::now()->year);

        $spend = Trip::netSpendForYear(Carbon::create($year));
        $tripCount = Trip::countForYear($year);
        $itemCount = Trip::itemCountForYear($year);

        return [
            Stat::make('Total spend', $spend->format()),
            Stat::make('Trips', (string) $tripCount),
            Stat::make('Items bought', (string) $itemCount),
            Stat::make('Average per trip', $tripCount > 0 ? $spend->divide($tripCount)->format() : '—'),
        ];
    }
}
