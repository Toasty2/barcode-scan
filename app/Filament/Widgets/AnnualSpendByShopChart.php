<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;

class AnnualSpendByShopChart extends ChartWidget
{
    use InteractsWithPageFilters;

    // Shown only on the Annual Summary page, not auto-attached to the
    // Dashboard.
    protected static bool $isDiscovered = false;

    public function getHeading(): string|Htmlable|null
    {
        return __('Spend by shop');
    }

    protected function getData(): array
    {
        $year = (int) ($this->pageFilters['year'] ?? now()->year);

        // Biggest segment first, rather than whatever order shops happen to
        // have been created in.
        $breakdown = Trip::netSpendByShopForYear($year)
            ->sortByDesc(fn (array $row) => $row['spend']->getMinorAmount()->toInt())
            ->values();

        return [
            'datasets' => [
                [
                    'data' => $breakdown->map(fn (array $row) => $row['spend']->getAmount()->toFloat())->all(),
                    'backgroundColor' => $breakdown->map(fn (array $row) => $row['shop']?->colour ?? '#6b7280')->all(),
                ],
            ],
            'labels' => $breakdown->map(fn (array $row) => $row['shop']?->name ?? __('No shop'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
