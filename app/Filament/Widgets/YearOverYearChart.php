<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class YearOverYearChart extends ChartWidget
{
    protected static ?int $sort = 4;

    public function getHeading(): string|Htmlable|null
    {
        return __('Spend by month, year over year');
    }

    // A qualitative palette (distinct hues, not shades of one colour) so
    // each year's line stays visually distinguishable regardless of how
    // many years are plotted at once. Cycles if there are ever more years
    // than colours.
    private const COLOURS = [
        '#2563eb', // blue
        '#dc2626', // red
        '#16a34a', // green
        '#9333ea', // purple
        '#d97706', // amber
        '#0891b2', // cyan
        '#db2777', // pink
        '#65a30d', // lime
    ];

    protected function getData(): array
    {
        $now = Carbon::now()->startOfMonth();

        $datasets = Trip::yearsWithTrips()->values()->map(function (int $year, int $index) use ($now) {
            $data = collect(range(1, 12))->map(function (int $month) use ($year, $now) {
                $monthStart = Carbon::create($year, $month, 1);

                // Months that haven't happened yet stay null (not 0), so the
                // line for the current year stops rather than dropping to
                // the axis for the remainder of the year.
                if ($monthStart->greaterThan($now)) {
                    return null;
                }

                return Trip::netSpendForMonth($monthStart)->toMajorUnits();
            });

            $colour = self::COLOURS[$index % count(self::COLOURS)];

            return [
                'label' => (string) $year,
                'data' => $data->all(),
                'borderColor' => $colour,
                'backgroundColor' => $colour,
                'fill' => false,
            ];
        });

        return [
            'datasets' => $datasets->all(),
            'labels' => collect(range(1, 12))->map(fn (int $month) => Carbon::create(2000, $month, 1)->format('M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
