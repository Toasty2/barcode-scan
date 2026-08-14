<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasResponsiveStatsColumns;
use App\Models\Product;
use App\Models\Purchase;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductOverview extends StatsOverviewWidget
{
    use HasResponsiveStatsColumns;

    // Shown only on a product's View page, not auto-attached to the
    // Dashboard.
    protected static bool $isDiscovered = false;

    public ?Product $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        return [
            Stat::make(__('Price'), $this->record->price->format()),
            Stat::make(__('Last confirmed price'), $this->record->last_confirmed->format('d/m/Y'))
                ->color($this->record->last_confirmed->lt(now()->subDays(90)) ? 'warning' : 'success'),
            $this->totalPurchasesStat(),
            $this->priceDeltaStat(),
        ];
    }

    private function priceDeltaStat(): Stat
    {
        $history = Purchase::priceHistoryForProduct($this->record);

        if ($history->isEmpty()) {
            return Stat::make(__('Price delta'), __('No purchase history yet'));
        }

        $firstPurchase = $history->first();
        $delta = $history->last()['price']->subtract($firstPurchase['price']);
        $firstDate = $firstPurchase['date']->format('d/m/Y');

        return Stat::make(__('Price delta'), $delta->format())
            ->description(match (true) {
                $delta->minorUnits === 0 => __('Same as first purchase on :date', ['date' => $firstDate]),
                $delta->isNegative() => __('Cheaper than first purchase on :date', ['date' => $firstDate]),
                default => __('More expensive than first purchase on :date', ['date' => $firstDate]),
            })
            ->color(match (true) {
                $delta->minorUnits === 0 => 'gray',
                $delta->isNegative() => 'success',
                default => 'danger',
            });
    }

    private function totalPurchasesStat(): Stat
    {
        $totalQuantity = (int) Purchase::where('product_id', $this->record->id)->sum('quantity');

        return Stat::make(__('Total purchases'), (string) $totalQuantity);
    }
}
