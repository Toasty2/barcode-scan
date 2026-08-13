<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Support\Money\Price;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class YearlyOverview extends StatsOverviewWidget
{
    // Shown on the Annual Summary page (App\Filament\Pages\AnnualSummary),
    // not auto-attached to the main Dashboard, which is kept lightweight.
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $stats = [];
        $previousYear = null;
        $previousSpend = null;

        foreach (Trip::yearsWithTrips() as $year) {
            $spend = Trip::netSpendForYear(Carbon::create($year));

            $stats[] = Stat::make((string) $year, $spend->format())
                ->description($previousSpend ? self::comparisonDescription($spend, $previousSpend, $previousYear) : null)
                ->color($previousSpend ? self::comparisonColor($spend, $previousSpend) : 'gray');

            $previousYear = $year;
            $previousSpend = $spend;
        }

        return $stats;
    }

    public static function comparisonDescription(Price $spend, Price $previousSpend, int $previousYear): string
    {
        $diff = $spend->subtract($previousSpend);

        return match (true) {
            $diff->minorUnits === 0 => "Same as {$previousYear}",
            $diff->isNegative() => $diff->abs()->format()." less than {$previousYear}",
            default => $diff->format()." more than {$previousYear}",
        };
    }

    public static function comparisonColor(Price $spend, Price $previousSpend): string
    {
        return match (true) {
            $spend->minorUnits === $previousSpend->minorUnits => 'gray',
            $spend->subtract($previousSpend)->isNegative() => 'success',
            default => 'danger',
        };
    }
}
