<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasResponsiveStatsColumns;
use App\Models\Trip;
use Brick\Money\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class YearlyOverview extends StatsOverviewWidget
{
    use HasResponsiveStatsColumns;

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

            $stats[] = Stat::make((string) $year, $spend->formatToLocale(config('money.default_locale')))
                ->description($previousSpend ? self::comparisonDescription($spend, $previousSpend, $previousYear) : null)
                ->color($previousSpend ? self::comparisonColor($spend, $previousSpend) : 'gray');

            $previousYear = $year;
            $previousSpend = $spend;
        }

        return $stats;
    }

    public static function comparisonDescription(Money $spend, Money $previousSpend, int $previousYear): string
    {
        $locale = config('money.default_locale');
        $diff = $spend->minus($previousSpend);

        return match (true) {
            $diff->isZero() => __('Same as :year', ['year' => $previousYear]),
            $diff->isNegative() => __(':amount less than :year', ['amount' => $diff->abs()->formatToLocale($locale), 'year' => $previousYear]),
            default => __(':amount more than :year', ['amount' => $diff->formatToLocale($locale), 'year' => $previousYear]),
        };
    }

    public static function comparisonColor(Money $spend, Money $previousSpend): string
    {
        return match (true) {
            $spend->isEqualTo($previousSpend) => 'gray',
            $spend->minus($previousSpend)->isNegative() => 'success',
            default => 'danger',
        };
    }
}
