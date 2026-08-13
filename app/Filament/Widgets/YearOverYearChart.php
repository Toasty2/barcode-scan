<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class YearOverYearChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Spend by month, year over year';

    protected function getData(): array
    {
        $now = Carbon::now()->startOfMonth();

        $datasets = Trip::yearsWithTrips()->map(function (int $year) use ($now) {
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

            return [
                'label' => (string) $year,
                'data' => $data->all(),
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
