<?php

namespace App\Filament\Widgets;

use App\Models\BudgetChange;
use App\Models\Trip;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SpendVsBudgetChart extends ChartWidget
{
    protected ?string $heading = 'Spend vs budget';

    private const MONTHS_SHOWN = 3;

    protected function getData(): array
    {
        $months = collect(range(self::MONTHS_SHOWN - 1, 0))
            ->map(fn (int $monthsAgo) => Carbon::now()->subMonths($monthsAgo)->startOfMonth());

        return [
            'datasets' => [
                [
                    'label' => 'Spend',
                    'data' => $months->map(fn (Carbon $month) => Trip::netSpendForMonth($month))->all(),
                ],
                [
                    'label' => 'Budget',
                    'data' => $months->map(fn (Carbon $month) => BudgetChange::amountForMonth($month))->all(),
                ],
            ],
            'labels' => $months->map(fn (Carbon $month) => $month->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
