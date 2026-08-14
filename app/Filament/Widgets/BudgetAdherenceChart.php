<?php

namespace App\Filament\Widgets;

use App\Models\BudgetChange;
use App\Models\Trip;
use App\Support\Charts\CategoricalPalette;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class BudgetAdherenceChart extends ChartWidget
{
    // Shown only on the Budget Adherence page, not auto-attached to the
    // Dashboard.
    protected static bool $isDiscovered = false;

    public function getHeading(): string|Htmlable|null
    {
        return __('Budget adherence');
    }

    protected function getData(): array
    {
        $months = BudgetChange::monthsWithBudget();

        $budgetColour = CategoricalPalette::colour(0);
        $spendColour = CategoricalPalette::colour(1);

        return [
            'datasets' => [
                [
                    'label' => __('Budget'),
                    'data' => $months->map(fn ($month) => BudgetChange::amountForMonth($month)?->getAmount()->toFloat())->all(),
                    'borderColor' => $budgetColour,
                    'backgroundColor' => $budgetColour,
                    'fill' => false,
                ],
                [
                    'label' => __('Spend'),
                    'data' => $months->map(fn ($month) => Trip::netSpendForMonth($month)->getAmount()->toFloat())->all(),
                    'borderColor' => $spendColour,
                    'backgroundColor' => $spendColour,
                    'fill' => false,
                ],
            ],
            'labels' => $months->map(fn ($month) => $month->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
