<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Purchase;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class ProductPriceHistoryChart extends ChartWidget
{
    // Shown only on a product's View page, not auto-attached to the
    // Dashboard.
    protected static bool $isDiscovered = false;

    public ?Product $record = null;

    public function getHeading(): string|Htmlable|null
    {
        return __('Price history');
    }

    protected function getData(): array
    {
        if (! $this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $history = Purchase::priceHistoryForProduct($this->record);

        return [
            'datasets' => [
                [
                    'label' => __('Price'),
                    'data' => $history->map(fn (array $row) => $row['price']->getAmount()->toFloat())->all(),
                ],
            ],
            'labels' => $history->map(fn (array $row) => $row['date']->format('d/m/Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
