<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class AnnualSpendByMonthChart extends ChartWidget
{
    use InteractsWithPageFilters;

    // Shown only on the Annual Summary page, not auto-attached to the
    // Dashboard.
    protected static bool $isDiscovered = false;

    public function getHeading(): string|Htmlable|null
    {
        return __('Spend by month');
    }

    protected function getData(): array
    {
        $year = (int) ($this->pageFilters['year'] ?? Carbon::now()->year);
        $now = Carbon::now()->startOfMonth();

        // Months that haven't happened yet (only relevant when the selected
        // year is the current one) stay null, not 0, so the bar simply
        // isn't drawn rather than reading as "nothing spent".
        $data = collect(range(1, 12))->map(function (int $month) use ($year, $now) {
            $monthStart = Carbon::create($year, $month, 1);

            if ($monthStart->greaterThan($now)) {
                return null;
            }

            return Trip::netSpendForMonth($monthStart)->getAmount()->toFloat();
        });

        return [
            'datasets' => [
                [
                    'label' => __('Spend'),
                    'data' => $data->all(),
                ],
            ],
            'labels' => collect(range(1, 12))->map(fn (int $month) => Carbon::create(2000, $month, 1)->format('M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
