<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SpendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Spend';

    public ?string $filter = '3m';

    protected function getFilters(): ?array
    {
        return [
            '3m' => 'Last 3 months',
            '6m' => 'Last 6 months',
            '1y' => 'Last year',
            '3y' => 'Last 3 years',
            '5y' => 'Last 5 years',
            'all' => 'All time',
        ];
    }

    protected function getData(): array
    {
        $months = $this->monthsForFilter($this->filter);

        return [
            'datasets' => [
                [
                    'label' => 'Spend',
                    'data' => $months->map(fn (Carbon $month) => Trip::netSpendForMonth($month)->toMajorUnits())->all(),
                ],
            ],
            'labels' => $months->map(fn (Carbon $month) => $month->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function monthsForFilter(?string $filter): Collection
    {
        $now = Carbon::now()->startOfMonth();

        $count = match ($filter) {
            '6m' => 6,
            '1y' => 12,
            '3y' => 36,
            '5y' => 60,
            'all' => $this->monthsSinceEarliestTrip($now),
            default => 3,
        };

        return collect(range($count - 1, 0))
            ->map(fn (int $monthsAgo) => $now->clone()->subMonths($monthsAgo));
    }

    private function monthsSinceEarliestTrip(Carbon $now): int
    {
        $earliest = Trip::min('shopped_on');

        if ($earliest === null) {
            return 1;
        }

        return Carbon::parse($earliest)->startOfMonth()->diffInMonths($now) + 1;
    }
}
